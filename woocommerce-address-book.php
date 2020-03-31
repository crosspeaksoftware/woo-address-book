<?php
/**
 * Plugin Name: WooCommerce Address Book
 * Description: Gives your customers the option to store multiple shipping addresses and retrieve them on checkout..
 * Version: 1.7.5
 * Author: Hall Internet Marketing
 * Author URI: https://www.hallme.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-address-book
 * WC tested up to: 4.0.1
 *
 * @package WooCommerce Address Book
 */

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );

	/**
	 * Deactivate the plugin if WooCommerce is not active.
	 *
	 * @since    1.0.0
	 */
	function wc_address_book_woocommerce_notice_error() {
		$class   = 'notice notice-error';
		$message = __( 'WooCommerce Address Book requires WooCommerce and has been deactivated.', 'woo-address-book' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_attr( $message ) );
	}
	add_action( 'admin_notices', 'wc_address_book_woocommerce_notice_error' );
	add_action( 'network_admin_notices', 'wc_address_book_woocommerce_notice_error' );
} else {
	require plugin_dir_path( __FILE__ ) . 'includes/class-wc-address-book.php';

	// Init Class.
	WC_Address_Book::get_instance();
}
