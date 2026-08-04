<?php
/**
 * monobank API client.
 *
 * @package Woo_Mono_Int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles monobank acquiring API requests.
 */
class Woo_Mono_Int_API {

	const BASE_URL = 'https://api.monobank.ua';

	/**
	 * Merchant token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Logger enabled flag.
	 *
	 * @var bool
	 */
	private $debug;

	/**
	 * Constructor.
	 *
	 * @param string $token Merchant token.
	 * @param bool   $debug Enable debug logs.
	 */
	public function __construct( $token, $debug = false ) {
		$this->token = (string) $token;
		$this->debug = (bool) $debug;
	}

	/**
	 * Creates a monobank invoice.
	 *
	 * @param array $payload Invoice payload.
	 * @return array
	 * @throws Exception When API request fails.
	 */
	public function create_invoice( array $payload ) {
		return $this->request( 'POST', '/api/merchant/invoice/create', $payload );
	}

	/**
	 * Gets invoice status.
	 *
	 * @param string $invoice_id Invoice ID.
	 * @return array
	 * @throws Exception When API request fails.
	 */
	public function get_invoice_status( $invoice_id ) {
		return $this->request(
			'GET',
			'/api/merchant/invoice/status',
			array(),
			array(
				'invoiceId' => $invoice_id,
			)
		);
	}

	/**
	 * Gets public key for webhook signature verification.
	 *
	 * @return string
	 * @throws Exception When API request fails.
	 */
	public function get_public_key() {
		$response = $this->request( 'GET', '/api/merchant/pubkey' );

		if ( empty( $response['key'] ) ) {
			throw new Exception( esc_html__( 'monobank public key is missing in API response.', 'woo-mono-int' ) );
		}

		return (string) $response['key'];
	}

	/**
	 * Makes an API request.
	 *
	 * @param string $method HTTP method.
	 * @param string $path API path.
	 * @param array  $body Request body.
	 * @param array  $query Query args.
	 * @return array
	 * @throws Exception When request fails.
	 */
	private function request( $method, $path, array $body = array(), array $query = array() ) {
		$url = self::BASE_URL . $path;

		if ( ! empty( $query ) ) {
			$url = add_query_arg( array_map( 'rawurlencode', $query ), $url );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'X-Token'       => $this->token,
				'X-Cms'         => 'WooCommerce',
				'X-Cms-Version' => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			),
		);

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		$this->log(
			'debug',
			'API request',
			array(
				'method' => $method,
				'url'    => $url,
				'body'   => $body,
			)
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$raw_body = (string) wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true );

		$this->log(
			'debug',
			'API response',
			array(
				'code' => $code,
				'body' => $data ? $data : $raw_body,
			)
		);

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $data ) && ! empty( $data['errText'] ) ? $data['errText'] : $raw_body;
			throw new Exception( esc_html( sprintf( 'monobank API error %1$d: %2$s', $code, $message ) ) );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Writes to WooCommerce logger.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( ! $this->debug && 'debug' === $level ) {
			return;
		}

		if ( function_exists( 'wc_get_logger' ) ) {
			$context['source'] = 'woo-mono-int';
			wc_get_logger()->log( $level, $message, $context );
		}
	}
}
