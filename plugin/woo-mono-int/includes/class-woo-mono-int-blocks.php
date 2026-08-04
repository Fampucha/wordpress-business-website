<?php
/**
 * WooCommerce Blocks integration for the monobank payment gateway.
 *
 * @package Woo_Mono_Int
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers monobank as a payment method in the Checkout Block UI.
 */
class Woo_Mono_Int_Blocks extends AbstractPaymentMethodType {

	/**
	 * Payment method name.
	 *
	 * @var string
	 */
	protected $name = Woo_Mono_Int_Gateway::ID;

	/**
	 * Gateway instance.
	 *
	 * @var Woo_Mono_Int_Gateway|null
	 */
	private $gateway = null;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . Woo_Mono_Int_Gateway::ID . '_settings', array() );

		if ( WC()->payment_gateways() ) {
			$gateways      = WC()->payment_gateways()->payment_gateways();
			$this->gateway = isset( $gateways[ Woo_Mono_Int_Gateway::ID ] ) ? $gateways[ Woo_Mono_Int_Gateway::ID ] : null;
		}
	}

	/**
	 * Checks whether the gateway should be shown in Checkout Blocks.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->gateway instanceof Woo_Mono_Int_Gateway && $this->gateway->is_available();
	}

	/**
	 * Registers frontend script for the Checkout Block payment method.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'woo-mono-int-blocks',
			WOO_MONO_INT_URL . 'assets/js/blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
			WOO_MONO_INT_VERSION,
			true
		);

		return array( 'woo-mono-int-blocks' );
	}

	/**
	 * Passes gateway settings to the Checkout Block script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway ? $this->gateway->title : $this->get_setting( 'title', __( 'monobank', 'woo-mono-int' ) ),
			'description' => $this->gateway ? $this->gateway->description : $this->get_setting( 'description', '' ),
			'supports'    => $this->get_supported_features(),
		);
	}
}
