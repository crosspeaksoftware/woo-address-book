<?php
/**
 * WooCommerce Address Book.
 *
 * @version  2.2.1
 * @package  WooCommerce Address Book
 */

/**
 * The WooCommerce Address Book settings page for the admin.
 *
 * @param array $settings_tabs The current array of settings tabs.
 * @return array
 */
function woo_address_book_settings_tab_init( $settings_tabs ) {
	$advanced_settings = $settings_tabs['advanced'];
	$settings_tabs['address_book'] = __( 'Address Book', 'woo-address-book' );

	// Unset and reindex the Advanced tab to remain last
	unset( $settings_tabs['advanced'] );
	$settings_tabs['advanced'] = $advanced_settings;

	return $settings_tabs;
}
// Late priority (50) ensures the other tabs are all set before adding the Address Book tab.
add_filter( 'woocommerce_settings_tabs_array', 'woo_address_book_settings_tab_init', 50 );

/**
 * Add admin fields to the Address Book settings page.
 */
function woo_address_book_settings_tab() {
	woocommerce_admin_fields( woo_address_book_general_settings() );
}
add_action( 'woocommerce_settings_address_book', 'woo_address_book_settings_tab');

/**
 * The WooCommerce Address Book settings page for the admin.
 *
 * @return array
 */
function woo_address_book_general_settings() {
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
		'desc'    => __( '"Add New Address" as default selection', 'woo-address-book' ),
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
		'desc'    => __( '"Add New Address" as default selection', 'woo-address-book' ),
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
		'title'             => __( 'Billing address save limit', 'woo-address-book' ),
		'desc'              => __( 'This sets the maximum number of billing addresses that each user can save. Set to 0 for unlimited.', 'woocommerce' ),
		'id'                => 'woo_address_book_billing_save_limit',
		'css'               => 'width:50px;',
		'default'           => '0',
		'desc_tip'          => false,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => 1,
		),
	);

	$settings[] = array(
		'title'             => __( 'Shipping address save limit', 'woo-address-book' ),
		'desc'              => __( 'This sets the maximum number of shipping addresses that each user can save. Set to 0 for unlimited.', 'woocommerce' ),
		'id'                => 'woo_address_book_shipping_save_limit',
		'css'               => 'width:50px;',
		'default'           => '0',
		'desc_tip'          => false,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => 1,
		),
	);

	$settings[] = array(
		'desc'    => __( 'Block readonly fields from being populated by changing address during checkout.', 'woo-address-book' ),
		'id'      => 'woo_address_book_block_readonly',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'desc'    => __( 'Use radio inputs instead of a select element for billing/shipping address on checkout.', 'woo-address-book' ),
		'id'      => 'woo_address_book_use_radio_input',
		'default' => 'no',
		'type'    => 'checkbox',
	);

	$settings[] = array(
		'type' => 'sectionend',
		'id'   => 'woo_address_book_options',
	);
	
	return $settings;
}
