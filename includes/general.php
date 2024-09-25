<?php
/**
 * WooCommerce Address Book General.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\General;

use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Load plugin textdomain for localization.
 *
 * @return void
 */
function load_plugin_textdomain() {
	\load_plugin_textdomain( 'woo-address-book', false, basename( dirname( __DIR__ ) ) . '/languages/' );
}
add_action( 'init', __NAMESPACE__ . '\load_plugin_textdomain' );

/**
 * Enqueue scripts and styles
 *
 * @return void
 */
function scripts_styles() {
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_register_style( 'woo-address-book', plugins_url( "/assets/css/style$min.css", __DIR__ ), array(), \CrossPeakSoftware\WooCommerce\AddressBook\PLUGIN_VERSION );
	wp_register_script( 'woo-address-book', plugins_url( "/assets/js/scripts$min.js", __DIR__ ), array( 'jquery', 'jquery-blockui' ), \CrossPeakSoftware\WooCommerce\AddressBook\PLUGIN_VERSION, true );

	wp_localize_script(
		'woo-address-book',
		'woo_address_book',
		array(
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'delete_security'     => wp_create_nonce( 'woo-address-book-delete' ),
			'default_security'    => wp_create_nonce( 'woo-address-book-default' ),
			'checkout_security'   => wp_create_nonce( 'woo-address-book-checkout' ),
			'delete_confirmation' => __( 'Are you sure you want to delete this address?', 'woo-address-book' ),
			'default_text'        => __( 'Default', 'woo-address-book' ),
			'allow_readonly'      => setting( 'block_readonly' ) === true ? 'no' : 'yes',
		)
	);

	if ( \is_account_page() || \is_checkout() ) {
		wp_enqueue_style( 'woo-address-book' );
		wp_enqueue_script( 'woo-address-book' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\scripts_styles' );
