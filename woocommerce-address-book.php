<?php
/**
 * Plugin Name: WooCommerce Address Book
 * Description: Gives your customers the option to store multiple shipping addresses and retrieve them on checkout..
 * Version: 2.6.4
 * Author: CrossPeak
 * Author URI: https://www.crosspeaksoftware.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-address-book
 * WC tested up to: 8.9.0
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
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, false );
		}
	}
);

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

	// Adds plugin settings.
	include_once dirname( __FILE__ ) . '/includes/settings.php';

	// Init Class.
	WC_Address_Book::get_instance();
}

add_action( 'in_plugin_update_message-woo-address-book/woocommerce-address-book.php', 'woo_address_book_update_notice', 10, 2 );
/**
 * Show plugin changes on the plugins screen.
 *
 * @param array    $args Unused parameter.
 * @param stdClass $response Plugin update response.
 */
function woo_address_book_update_notice( $args, $response ) {
	if ( version_compare( $response->new_version, '3.0', '<' ) ) {
		return;
	}
	echo '<br><span style="display: inline-block; background-color: #d54e21; padding: 5px 10px 5px 10px; color: #f9f9f9; margin-top: 10px"><b>Version 3.x+</b> introduces new data structure and templates. This will <b>break some custom user modifications</b> especially if you access the user meta directly! Please test it before upgrading.</span>';
}
