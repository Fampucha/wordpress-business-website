<?php
/**
 * monobank webhook handler.
 *
 * @package Woo_Mono_Int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles monobank callbacks.
 */
class Woo_Mono_Int_Webhook {

	const PUBKEY_OPTION = 'woo_mono_int_pubkey';

	/**
	 * Handles webhook request.
	 */
	public static function handle() {
		$settings = get_option( 'woocommerce_woo_mono_int_settings', array() );
		$token    = isset( $settings['merchant_token'] ) ? (string) $settings['merchant_token'] : '';
		$debug    = isset( $settings['debug'] ) && 'yes' === $settings['debug'];
		$verify   = ! isset( $settings['verify_signature'] ) || 'yes' === $settings['verify_signature'];
		$api      = new Woo_Mono_Int_API( $token, $debug );

		$body = file_get_contents( 'php://input' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			status_header( 405 );
			exit;
		}

		if ( empty( $token ) ) {
			$api->log( 'error', 'Webhook rejected: merchant token is not configured.' );
			status_header( 403 );
			exit;
		}

		if ( $verify && ! self::verify_signature( $body, $api ) ) {
			$api->log( 'error', 'Webhook rejected: invalid X-Sign signature.' );
			status_header( 403 );
			exit;
		}

		$payload = json_decode( $body, true );

		if ( ! is_array( $payload ) || empty( $payload['invoiceId'] ) || empty( $payload['status'] ) ) {
			$api->log( 'error', 'Webhook rejected: invalid payload.', array( 'payload' => $payload ) );
			status_header( 400 );
			exit;
		}

		$order = self::find_order( sanitize_text_field( $payload['invoiceId'] ), $payload );

		if ( ! $order ) {
			$api->log( 'error', 'Webhook rejected: order was not found.', array( 'payload' => $payload ) );
			status_header( 404 );
			exit;
		}

		self::update_order_from_payload( $order, $payload );

		$api->log(
			'info',
			'Webhook processed.',
			array(
				'order_id'  => $order->get_id(),
				'invoiceId' => $payload['invoiceId'],
				'status'    => $payload['status'],
			)
		);

		status_header( 200 );
		echo 'OK'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Verifies X-Sign header.
	 *
	 * @param string           $body Raw body.
	 * @param Woo_Mono_Int_API $api API client.
	 * @return bool
	 */
	private static function verify_signature( $body, Woo_Mono_Int_API $api ) {
		$signature = isset( $_SERVER['HTTP_X_SIGN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_SIGN'] ) ) : '';

		if ( empty( $signature ) ) {
			return false;
		}

		$pubkey = get_option( self::PUBKEY_OPTION, '' );

		if ( empty( $pubkey ) || ! self::verify_with_key( $body, $signature, $pubkey ) ) {
			try {
				$pubkey = $api->get_public_key();
				update_option( self::PUBKEY_OPTION, $pubkey, false );
			} catch ( Exception $exception ) {
				$api->log( 'error', 'Unable to fetch monobank public key.', array( 'error' => $exception->getMessage() ) );
				return false;
			}
		}

		return self::verify_with_key( $body, $signature, $pubkey );
	}

	/**
	 * Verifies payload signature with provided key.
	 *
	 * @param string $body Raw body.
	 * @param string $signature_base64 Signature.
	 * @param string $pubkey_base64 Public key.
	 * @return bool
	 */
	private static function verify_with_key( $body, $signature_base64, $pubkey_base64 ) {
		$signature = base64_decode( $signature_base64, true );
		$key_pem   = base64_decode( $pubkey_base64, true );

		if ( false === $signature || false === $key_pem || ! function_exists( 'openssl_verify' ) ) {
			return false;
		}

		$public_key = openssl_get_publickey( $key_pem );

		if ( false === $public_key ) {
			return false;
		}

		$result = openssl_verify( $body, $signature, $public_key, OPENSSL_ALGO_SHA256 );

		if ( PHP_VERSION_ID < 80000 ) {
			openssl_free_key( $public_key );
		}

		return 1 === $result;
	}

	/**
	 * Finds order by invoice ID or reference.
	 *
	 * @param string $invoice_id Invoice ID.
	 * @param array  $payload Webhook payload.
	 * @return WC_Order|false
	 */
	private static function find_order( $invoice_id, array $payload ) {
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'objects',
				'meta_key'   => '_woo_mono_int_invoice_id',
				'meta_value' => $invoice_id,
			)
		);

		if ( ! empty( $orders[0] ) ) {
			return $orders[0];
		}

		if ( empty( $payload['reference'] ) ) {
			return false;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'return'     => 'objects',
				'meta_key'   => '_woo_mono_int_reference',
				'meta_value' => sanitize_text_field( $payload['reference'] ),
			)
		);

		return ! empty( $orders[0] ) ? $orders[0] : false;
	}

	/**
	 * Updates order using webhook payload.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $payload Webhook payload.
	 */
	public static function update_order_from_payload( WC_Order $order, array $payload ) {
		$status     = sanitize_text_field( $payload['status'] );
		$invoice_id = sanitize_text_field( $payload['invoiceId'] );

		$order->update_meta_data( '_woo_mono_int_invoice_id', $invoice_id );
		$order->update_meta_data( '_woo_mono_int_invoice_status', $status );

		if ( isset( $payload['paymentInfo']['rrn'] ) ) {
			$order->update_meta_data( '_woo_mono_int_rrn', sanitize_text_field( $payload['paymentInfo']['rrn'] ) );
		}

		if ( isset( $payload['paymentInfo']['approvalCode'] ) ) {
			$order->update_meta_data( '_woo_mono_int_approval_code', sanitize_text_field( $payload['paymentInfo']['approvalCode'] ) );
		}

		if ( isset( $payload['modifiedDate'] ) ) {
			$order->update_meta_data( '_woo_mono_int_modified_date', sanitize_text_field( $payload['modifiedDate'] ) );
		}

		switch ( $status ) {
			case 'success':
				if ( ! $order->is_paid() ) {
					$transaction_id = isset( $payload['paymentInfo']['tranId'] ) ? sanitize_text_field( $payload['paymentInfo']['tranId'] ) : $invoice_id;
					$order->payment_complete( $transaction_id );
				}
				$order->add_order_note( __( 'monobank payment successful.', 'woo-mono-int' ) );
				break;

			case 'processing':
			case 'created':
				$order->add_order_note(
					sprintf(
						/* translators: %s: invoice status. */
						__( 'monobank invoice status: %s.', 'woo-mono-int' ),
						$status
					)
				);
				break;

			case 'failure':
				$order->update_status( 'failed', __( 'monobank payment failed.', 'woo-mono-int' ) );
				break;

			case 'expired':
				$order->update_status( 'cancelled', __( 'monobank invoice expired.', 'woo-mono-int' ) );
				break;

			case 'reversed':
				$order->update_status( 'refunded', __( 'monobank payment reversed.', 'woo-mono-int' ) );
				break;

			default:
				$order->add_order_note(
					sprintf(
						/* translators: %s: invoice status. */
						__( 'monobank webhook received unknown status: %s.', 'woo-mono-int' ),
						$status
					)
				);
				break;
		}

		$order->save();
	}
}
