<?php
/**
 * WooCommerce monobank payment gateway.
 *
 * @package Woo_Mono_Int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment gateway class.
 */
class Woo_Mono_Int_Gateway extends WC_Payment_Gateway {

	const ID = 'woo_mono_int';

	/**
	 * Merchant token.
	 *
	 * @var string
	 */
	public $merchant_token;

	/**
	 * Test mode flag.
	 *
	 * @var bool
	 */
	public $test_mode;

	/**
	 * Debug log flag.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * Verify webhook signature flag.
	 *
	 * @var bool
	 */
	public $verify_signature;

	/**
	 * Invoice validity in seconds.
	 *
	 * @var int
	 */
	public $invoice_validity;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'woo_mono_int_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'monobank acquiring', 'woo-mono-int' );
		$this->method_description = __( 'Accept test payments through monobank acquiring invoices.', 'woo-mono-int' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->enabled           = $this->get_option( 'enabled' );
		$this->merchant_token    = $this->get_option( 'merchant_token' );
		$this->test_mode         = 'yes' === $this->get_option( 'test_mode', 'yes' );
		$this->debug             = 'yes' === $this->get_option( 'debug', 'no' );
		$this->verify_signature  = 'yes' === $this->get_option( 'verify_signature', 'yes' );
		$this->invoice_validity  = absint( $this->get_option( 'invoice_validity', 3600 ) );
		$this->order_button_text = __( 'Pay with monobank', 'woo-mono-int' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Initializes settings fields.
	 */
	public function init_form_fields() {
		$webhook_url = WC()->api_request_url( 'woo_mono_int' );

		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Enable/Disable', 'woo-mono-int' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable monobank payment method', 'woo-mono-int' ),
				'default' => 'no',
			),
			'test_mode'        => array(
				'title'       => __( 'Test mode', 'woo-mono-int' ),
				'type'        => 'checkbox',
				'label'       => __( 'Use monobank test token from https://api.monobank.ua/', 'woo-mono-int' ),
				'description' => __( 'monobank uses the same API endpoint for test mode. Test behavior depends on the token you paste below.', 'woo-mono-int' ),
				'default'     => 'yes',
			),
			'title'            => array(
				'title'       => __( 'Title', 'woo-mono-int' ),
				'type'        => 'safe_text',
				'description' => __( 'Payment method title shown at checkout.', 'woo-mono-int' ),
				'default'     => __( 'monobank', 'woo-mono-int' ),
				'desc_tip'    => true,
			),
			'description'      => array(
				'title'       => __( 'Description', 'woo-mono-int' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown at checkout.', 'woo-mono-int' ),
				'default'     => __( 'Pay by card through the monobank test payment page.', 'woo-mono-int' ),
				'desc_tip'    => true,
			),
			'merchant_token'   => array(
				'title'       => __( 'Merchant token', 'woo-mono-int' ),
				'type'        => 'merchant_token',
				'description' => __( 'Leave empty to keep the saved token. Enter a new token only when you need to replace it.', 'woo-mono-int' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'invoice_validity' => array(
				'title'             => __( 'Invoice validity', 'woo-mono-int' ),
				'type'              => 'number',
				'description'       => __( 'Invoice lifetime in seconds. Default: 3600.', 'woo-mono-int' ),
				'default'           => 3600,
				'custom_attributes' => array(
					'min'  => 60,
					'step' => 60,
				),
			),
			'verify_signature' => array(
				'title'       => __( 'Webhook signature', 'woo-mono-int' ),
				'type'        => 'checkbox',
				'label'       => __( 'Verify X-Sign webhook signature', 'woo-mono-int' ),
				'description' => __( 'Recommended. The public key is fetched from monobank and cached.', 'woo-mono-int' ),
				'default'     => 'yes',
			),
			'debug'            => array(
				'title'       => __( 'Debug log', 'woo-mono-int' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable WooCommerce logs', 'woo-mono-int' ),
				'description' => __( 'Logs are written under WooCommerce > Status > Logs with source woo-mono-int.', 'woo-mono-int' ),
				'default'     => 'no',
			),
			'webhook_url'      => array(
				'title'       => __( 'Webhook URL', 'woo-mono-int' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: webhook URL. */
					__( 'Use this URL for monobank callbacks: %s', 'woo-mono-int' ),
					'<code>' . esc_html( $webhook_url ) . '</code>'
				),
			),
		);
	}

	/**
	 * Renders merchant token field without exposing the saved token in HTML.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_merchant_token_html( $key, $data ) {
		$field_key     = $this->get_field_key( $key );
		$defaults      = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);
		$data          = wp_parse_args( $data, $defaults );
		$has_token     = '' !== (string) $this->get_option( $key, '' );
		$placeholder   = $has_token ? __( 'Token is saved. Leave empty to keep it.', 'woo-mono-int' ) : '';
		$placeholder   = $data['placeholder'] ? $data['placeholder'] : $placeholder;

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>">
					<?php echo esc_html( $data['title'] ); ?>
					<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
				</label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $data['title'] ); ?></span></legend>
					<input
						class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>"
						type="password"
						name="<?php echo esc_attr( $field_key ); ?>"
						id="<?php echo esc_attr( $field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value=""
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php disabled( $data['disabled'], true ); ?>
						<?php echo wp_kses_post( $this->get_custom_attribute_html( $data ) ); ?>
					>
					<?php echo wp_kses_post( $this->get_description_html( $data ) ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validates the merchant token setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	public function validate_merchant_token_field( $key, $value ) {
		$value = sanitize_text_field( wp_unslash( $value ) );

		if ( '' === $value ) {
			return $this->get_option( $key, '' );
		}

		return $value;
	}

	/**
	 * Validates invoice validity.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Raw value.
	 * @return int
	 */
	public function validate_invoice_validity_field( $key, $value ) {
		$value = absint( $value );

		return max( 60, $value );
	}

	/**
	 * Processes and validates admin options.
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$posted_enabled = isset( $_POST[ $this->plugin_id . $this->id . '_enabled' ] ) ? wc_clean( wp_unslash( $_POST[ $this->plugin_id . $this->id . '_enabled' ] ) ) : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted_token   = isset( $_POST[ $this->plugin_id . $this->id . '_merchant_token' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_id . $this->id . '_merchant_token' ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$saved_token    = $this->get_option( 'merchant_token', '' );

		if ( '1' === $posted_enabled && '' === $posted_token && '' === $saved_token ) {
			WC_Admin_Settings::add_error( __( 'Merchant token is required when monobank payment method is enabled.', 'woo-mono-int' ) );
			return false;
		}

		return parent::process_admin_options();
	}

	/**
	 * Checks gateway availability.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! parent::is_available() ) {
			return false;
		}

		if ( empty( $this->merchant_token ) ) {
			return false;
		}

		if ( function_exists( 'get_woocommerce_currency' ) && 'UAH' !== get_woocommerce_currency() ) {
			return false;
		}

		return true;
	}

	/**
	 * Processes checkout payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order was not found.', 'woo-mono-int' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( 'UAH' !== $order->get_currency() ) {
			wc_add_notice( __( 'monobank acquiring accepts only UAH orders in this integration.', 'woo-mono-int' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( empty( $this->merchant_token ) ) {
			wc_add_notice( __( 'monobank merchant token is not configured.', 'woo-mono-int' ), 'error' );
			return array( 'result' => 'failure' );
		}

		try {
			$api     = new Woo_Mono_Int_API( $this->merchant_token, $this->debug );
			$payload = $this->build_invoice_payload( $order );
			$result  = $api->create_invoice( $payload );

			if ( empty( $result['invoiceId'] ) || empty( $result['pageUrl'] ) ) {
				throw new Exception( esc_html__( 'monobank did not return invoiceId or pageUrl.', 'woo-mono-int' ) );
			}

			$order->update_meta_data( '_woo_mono_int_invoice_id', sanitize_text_field( $result['invoiceId'] ) );
			$order->update_meta_data( '_woo_mono_int_page_url', esc_url_raw( $result['pageUrl'] ) );
			$order->update_meta_data( '_woo_mono_int_invoice_status', 'created' );
			$order->update_meta_data( '_woo_mono_int_reference', $payload['merchantPaymInfo']['reference'] );
			$order->save();

			$order->update_status( 'pending', __( 'monobank invoice created. Awaiting payment.', 'woo-mono-int' ) );

			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}

			return array(
				'result'   => 'success',
				'redirect' => esc_url_raw( $result['pageUrl'] ),
			);
		} catch ( Exception $exception ) {
			$this->log(
				'error',
				'Invoice creation failed',
				array(
					'order_id' => $order_id,
					'error'    => $exception->getMessage(),
				)
			);

			$order->add_order_note(
				sprintf(
					/* translators: %s: error message. */
					__( 'monobank invoice creation failed: %s', 'woo-mono-int' ),
					$exception->getMessage()
				)
			);

			wc_add_notice( __( 'Payment initialization failed. Please try again or choose another payment method.', 'woo-mono-int' ), 'error' );
			return array( 'result' => 'failure' );
		}
	}

	/**
	 * Outputs thank you page instructions.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$invoice_id = $order->get_meta( '_woo_mono_int_invoice_id' );

		if ( $invoice_id && ! $order->is_paid() && $this->merchant_token ) {
			try {
				$api    = new Woo_Mono_Int_API( $this->merchant_token, $this->debug );
				$status = $api->get_invoice_status( $invoice_id );

				if ( ! empty( $status['status'] ) ) {
					Woo_Mono_Int_Webhook::update_order_from_payload( $order, $status );
				}
			} catch ( Exception $exception ) {
				$this->log(
					'error',
					'Unable to refresh invoice status on thank you page.',
					array(
						'order_id' => $order_id,
						'error'    => $exception->getMessage(),
					)
				);
			}
		}

		if ( $invoice_id ) {
			echo '<p>' . esc_html__( 'monobank invoice ID:', 'woo-mono-int' ) . ' <code>' . esc_html( $invoice_id ) . '</code></p>';
		}
	}

	/**
	 * Builds invoice payload.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	private function build_invoice_payload( WC_Order $order ) {
		$order_id  = $order->get_id();
		$reference = 'wc-order-' . $order_id . '-' . wp_generate_password( 8, false, false );
		$amount    = (int) round( (float) $order->get_total() * 100 );
		$items     = array();

		foreach ( $order->get_items() as $item ) {
			$quantity = max( 1, (int) $item->get_quantity() );
			$total    = (int) round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) * 100 );
			$unit_sum = (int) round( $total / $quantity );

			$items[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'qty'   => $quantity,
				'sum'   => $unit_sum,
				'total' => $total,
				'unit'  => 'pcs.',
				'code'  => (string) $item->get_product_id(),
			);
		}

		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$total = (int) round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) * 100 );

			if ( $total <= 0 ) {
				continue;
			}

			$items[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'qty'   => 1,
				'sum'   => $total,
				'total' => $total,
				'unit'  => 'service',
				'code'  => 'shipping',
			);
		}

		foreach ( $order->get_items( 'fee' ) as $item ) {
			$total = (int) round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) * 100 );

			if ( $total <= 0 ) {
				continue;
			}

			$items[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'qty'   => 1,
				'sum'   => $total,
				'total' => $total,
				'unit'  => 'service',
				'code'  => 'fee',
			);
		}

		$payload = array(
			'amount'           => $amount,
			'ccy'              => 980,
			'redirectUrl'      => $this->get_return_url( $order ),
			'webHookUrl'      => WC()->api_request_url( 'woo_mono_int' ),
			'validity'         => $this->invoice_validity,
			'paymentType'      => 'debit',
			'merchantPaymInfo' => array(
				'reference'      => $reference,
				'destination'    => sprintf(
					/* translators: %s: order number. */
					__( 'Payment for order %s', 'woo-mono-int' ),
					$order->get_order_number()
				),
				'comment'        => sprintf(
					/* translators: %s: order number. */
					__( 'WooCommerce order %s', 'woo-mono-int' ),
					$order->get_order_number()
				),
				'customerEmails' => array_filter( array( $order->get_billing_email() ) ),
				'metadata'       => array(
					'order_id'  => (string) $order_id,
					'order_key' => $order->get_order_key(),
					'test_mode' => $this->test_mode ? 'yes' : 'no',
				),
			),
		);

		$items_total = array_sum( wp_list_pluck( $items, 'total' ) );

		if ( $items && $items_total === $amount ) {
			$payload['merchantPaymInfo']['basketOrder'] = $items;
		}

		return $payload;
	}

	/**
	 * Writes log message.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 */
	private function log( $level, $message, array $context = array() ) {
		$api = new Woo_Mono_Int_API( $this->merchant_token, $this->debug );
		$api->log( $level, $message, $context );
	}
}
