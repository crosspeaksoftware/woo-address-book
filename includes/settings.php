<?php
/**
 * WooCommerce Address Book.
 *
 * @version  2.0.0
 * @package  WooCommerce Address Book
 */

add_filter( 'woocommerce_general_settings', 'woo_address_book_general_settings' );

/**
 * The WooCommerce Address Book settings page for the admin.
 *
 * @param array $settings The current settings array.
 * @return array
 */
function woo_address_book_general_settings( $settings ) {
	$settings[] = array(
		'title' => __( 'WooCommerce Address Book options', 'woo-address-book' ),
		'type'  => 'title',
		'desc'  => __( 'Setting options for WooCommerce Address Book.', 'woo-address-book' ),
		'id'    => 'woo_address_book_options',
	);

	$settings[] = array(
		'title'   => __( 'Billing address book', 'woo-address-book' ),
		'desc'    => __( 'Enable billing address book', 'woo-address-book' ),
		'id'      => 'woo_address_book_billing_enable',
		'default' => 'yes',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'desc'    => __( 'Add New Address as default selection', 'woo-address-book' ),
		'id'      => 'woo_address_book_billing_default_to_new_address',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'desc'    => __( 'Enable setting Billing Address Nickname during Checkout', 'woo-address-book' ),
		'id'      => 'woo_address_book_billing_address_nickname_checkout',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'title'   => __( 'Shipping address book', 'woo-address-book' ),
		'desc'    => __( 'Enable shipping address book', 'woo-address-book' ),
		'id'      => 'woo_address_book_shipping_enable',
		'default' => 'yes',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'desc'    => __( 'Add New Address as default selection', 'woo-address-book' ),
		'id'      => 'woo_address_book_shipping_default_to_new_address',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'desc'    => __( 'Enable setting Shipping Address Nickname during Checkout', 'woo-address-book' ),
		'id'      => 'woo_address_book_shipping_address_nickname_checkout',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'woo_address_book_options',
	);

	return $settings;
}
