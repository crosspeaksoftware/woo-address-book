<?php
/**
 * Plugin Name: WooCommerce Address Book
 * Description: Gives your customers the option to store multiple shipping and/or billing addresses and retrieve them on checkout.
 * Version: 3.0.2.13
 * Author: CrossPeak
 * Author URI: https://www.crosspeaksoftware.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-address-book
 * WC tested up to: 8.9.1
 * Requires PHP: 7.4
 * Requires at least: 6.0
 *
 * @package WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Declare HPOS compatibility.
 *
 * @see https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
		}
	}
);

if ( ! function_exists( __NAMESPACE__ . '\woocommerce_notice_error' ) ) {
	/**
	 * Add Notice if WooCommerce is not active.
	 *
	 * @return void
	 */
	function woocommerce_notice_error() {
		$class   = 'notice notice-error';
		$message = __( 'WooCommerce Address Book requires WooCommerce to be active.', 'woo-address-book' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_attr( $message ) );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return void
	 */
	function woocommerce_check() {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			add_action( 'admin_notices', __NAMESPACE__ . '\woocommerce_notice_error' );
			add_action( 'network_admin_notices', __NAMESPACE__ . '\woocommerce_notice_error' );
		}
	}
	add_action( 'plugins_loaded', __NAMESPACE__ . '\woocommerce_check' );
}

if ( ! function_exists( '\CrossPeakSoftware\WooCommerce\AddressBook\get_address_book' ) ) {
	require __DIR__ . '/includes/class-address-book.php';
	require __DIR__ . '/includes/address-book.php';
	require __DIR__ . '/includes/general.php';
	require __DIR__ . '/includes/validation.php';
	require __DIR__ . '/includes/nickname.php';
	require __DIR__ . '/includes/ajax.php';
	require __DIR__ . '/includes/import.php';
	require __DIR__ . '/includes/export.php';
	require __DIR__ . '/includes/subscriptions.php';
	require __DIR__ . '/includes/api.php';
	require __DIR__ . '/includes/settings.php';
	require __DIR__ . '/deprecations/class-wc-address-book.php';
}
