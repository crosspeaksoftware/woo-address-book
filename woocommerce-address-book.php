<?php
/**
 * Plugin Name: WooCommerce Address Book
 * Description: Gives your customers the option to store multiple shipping and/or billing addresses and retrieve them on checkout.
 * Version: 3.0.0
 * Author: CrossPeak
 * Author URI: https://www.crosspeaksoftware.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-address-book
 * WC tested up to: 8.0.0
 *
 * @package WooCommerce Address Book
 */

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
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Add Notice if WooCommerce is not active.
 */
function wc_address_book_woocommerce_notice_error() {
	$class   = 'notice notice-error';
	$message = __( 'WooCommerce Address Book requires WooCommerce to be active.', 'woo-address-book' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_attr( $message ) );
}

/**
 * Check if WooCommerce is active.
 */
function wc_address_book_woocommerce_check() {
	if ( ! class_exists( 'WooCommerce', false ) ) {
		add_action( 'admin_notices', 'wc_address_book_woocommerce_notice_error' );
		add_action( 'network_admin_notices', 'wc_address_book_woocommerce_notice_error' );
	}
}
add_action( 'plugins_loaded', 'wc_address_book_woocommerce_check' );

if ( ! class_exists( 'WC_Address_Book', false ) ) {
	require __DIR__ . '/includes/class-wc-address-book.php';

	// Adds plugin settings.
	require __DIR__ . '/includes/settings.php';
}

// Init Class.
WC_Address_Book::get_instance();
