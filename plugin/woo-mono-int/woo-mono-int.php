<?php
/**
 * Plugin Name: Woo Mono Int
 * Description: Test monobank acquiring payment gateway for WooCommerce.
 * Version: 1.0.0
 * Author: Eko Plastics
 * Text Domain: woo-mono-int
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 10.0
 *
 * @package Woo_Mono_Int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOO_MONO_INT_VERSION', '1.0.0' );
define( 'WOO_MONO_INT_FILE', __FILE__ );
define( 'WOO_MONO_INT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_MONO_INT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declares WooCommerce feature compatibility.
 */
function woo_mono_int_declare_wc_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'woo_mono_int_declare_wc_compatibility' );

/**
 * Loads the gateway after WooCommerce is available.
 */
function woo_mono_int_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'woo_mono_int_missing_woocommerce_notice' );
		return;
	}

	require_once WOO_MONO_INT_PATH . 'includes/class-woo-mono-int-api.php';
	require_once WOO_MONO_INT_PATH . 'includes/class-woo-mono-int-gateway.php';
	require_once WOO_MONO_INT_PATH . 'includes/class-woo-mono-int-webhook.php';

	add_filter( 'woocommerce_payment_gateways', 'woo_mono_int_add_gateway' );

	if ( did_action( 'woocommerce_blocks_loaded' ) ) {
		woo_mono_int_register_blocks_integration();
	} else {
		add_action( 'woocommerce_blocks_loaded', 'woo_mono_int_register_blocks_integration' );
	}

	add_action( 'woocommerce_api_woo_mono_int', array( 'Woo_Mono_Int_Webhook', 'handle' ) );
	add_action( 'woocommerce_admin_order_data_after_billing_address', 'woo_mono_int_display_order_payment_meta' );
}
add_action( 'plugins_loaded', 'woo_mono_int_init', 11 );

/**
 * Adds the gateway to WooCommerce.
 *
 * @param array $gateways Gateway classes.
 * @return array
 */
function woo_mono_int_add_gateway( $gateways ) {
	$gateways[] = 'Woo_Mono_Int_Gateway';

	return $gateways;
}

/**
 * Registers the gateway for WooCommerce Checkout Blocks.
 */
function woo_mono_int_register_blocks_integration() {
	if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once WOO_MONO_INT_PATH . 'includes/class-woo-mono-int-blocks.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( $payment_method_registry ) {
			$payment_method_registry->register( new Woo_Mono_Int_Blocks() );
		}
	);
}

/**
 * Shows an admin notice when WooCommerce is missing.
 */
function woo_mono_int_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Woo Mono Int requires WooCommerce to be installed and active.', 'woo-mono-int' );
	echo '</p></div>';
}

/**
 * Adds plugin action links.
 *
 * @param array $links Plugin links.
 * @return array
 */
function woo_mono_int_plugin_action_links( $links ) {
	$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_mono_int' );
	$settings     = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $settings_url ),
		esc_html__( 'Settings', 'woo-mono-int' )
	);

	array_unshift( $links, $settings );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_mono_int_plugin_action_links' );

/**
 * Displays monobank payment data in the order admin screen.
 *
 * @param WC_Order $order Order.
 */
function woo_mono_int_display_order_payment_meta( $order ) {
	if ( ! $order instanceof WC_Order || 'woo_mono_int' !== $order->get_payment_method() ) {
		return;
	}

	$invoice_id = $order->get_meta( '_woo_mono_int_invoice_id' );
	$status     = $order->get_meta( '_woo_mono_int_invoice_status' );
	$rrn        = $order->get_meta( '_woo_mono_int_rrn' );

	echo '<div class="address">';
	echo '<p><strong>' . esc_html__( 'monobank payment', 'woo-mono-int' ) . '</strong></p>';

	if ( $invoice_id ) {
		echo '<p>' . esc_html__( 'Invoice ID:', 'woo-mono-int' ) . ' <code>' . esc_html( $invoice_id ) . '</code></p>';
	}

	if ( $status ) {
		echo '<p>' . esc_html__( 'Status:', 'woo-mono-int' ) . ' ' . esc_html( $status ) . '</p>';
	}

	if ( $rrn ) {
		echo '<p>' . esc_html__( 'RRN:', 'woo-mono-int' ) . ' <code>' . esc_html( $rrn ) . '</code></p>';
	}

	echo '</div>';
}
