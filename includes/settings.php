<?php

/**
 * WooCommerce Address Book.
 *
 * @version  1.8.0
 * @package  WooCommerce Address Book
 */

add_filter( 'woocommerce_general_settings', 'wc_address_book_general_settings' );

function wc_address_book_general_settings( $settings ) {

	$settings[] = array('title' => __( 'WooCommerce Address Book options', 'woo-address-book' ), 'type' => 'title', 'desc' => __( 'Setting options for WooCommerce Address Book.', 'woo-address-book' ), 'id' => 'woo_address_book_options');

	$settings[] = array(
		'title'		 => __( 'Billing address book', 'woo-address-book' ),
		'desc'		 => __( 'Enable billing address book', 'woo-address-book' ),
		'id'		 => 'woo_address_book_billing_enable',
		'default'	 => 'yes',
		'type'		 => 'checkbox',
	);

	$settings[] = array(
		'desc'		 => __( 'Add New Address as default selection', 'woo-address-book' ),
		'id'		 => 'woo_address_book_billing_default_to_new_address',
		'default'	 => 'no',
		'type'		 => 'checkbox',
	);

	$settings[] = array(
		'title'		 => __( 'Shipping address book', 'woo-address-book' ),
		'desc'		 => __( 'Enable shipping address book', 'woo-address-book' ),
		'id'		 => 'woo_address_book_shipping_enable',
		'default'	 => 'yes',
		'type'		 => 'checkbox',
	);

	$settings[] = array(
		'desc'		 => __( 'Add New Address as default selection', 'woo-address-book' ),
		'id'		 => 'woo_address_book_shipping_default_to_new_address',
		'default'	 => 'no',
		'type'		 => 'checkbox',
	);

	$settings[] = array('type' => 'sectionend', 'id' => 'woo_address_book_options');

	return $settings;
}
