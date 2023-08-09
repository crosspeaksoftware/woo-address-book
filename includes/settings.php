<?php
/**
 * WooCommerce Address Book.
 *
 * @version  2.2.1
 * @package  WooCommerce Address Book
 */

/**
 * Add Address Book settings page to the WooCommerce settings tabs.
 *
 * @param array $tabs The current settings tabs array.
 * @return array
 */
function woo_address_book_add_settings_tab( $tabs ) {
	$tabs['address_book'] = __( 'Address Book', 'woo-address-book' );
	return $tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'woo_address_book_add_settings_tab', 50 );

/**
 * The WooCommerce Address Book settings for the admin.
 */
function woo_address_book_get_settings() {
	return array(
		array(
			'title' => __( 'WooCommerce Address Book options', 'woo-address-book' ),
			'type'  => 'title',
			'desc'  => __( 'Setting options for WooCommerce Address Book plugin.', 'woo-address-book' ),
			'id'    => 'woo_address_book_options',
		),
		array(
			'title'           => __( 'Billing address book', 'woo-address-book' ),
			'desc'            => __( 'Enable billing address book', 'woo-address-book' ),
			'id'              => 'woo_address_book_billing_enable',
			'default'         => 'yes',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'start',
			'show_if_checked' => 'option',
		),
		array(
			'desc'            => __( '"Add New Address" as default selection', 'woo-address-book' ),
			'desc_tip'        => 'When checked, the billing address book will default to "Add New Address" on the checkout page instead of the default address. The default address will still be used if the address book limit is set and reached.',
			'id'              => 'woo_address_book_billing_default_to_new_address',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => '',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'            => __( 'Enable setting Billing Address Nickname during Checkout', 'woo-address-book' ),
			'desc_tip'        => 'When checked, you can set a nickname for the billing address on the checkout page. When unchecked, the addresses will not have a nickname unless added in the "My account" menu.',
			'id'              => 'woo_address_book_billing_address_nickname_checkout',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'end',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'              => __( '<strong>Billing Address Book Limit</strong></p><p class="description">This sets the maximum number of billing addresses that each user can save. Set to 0 for unlimited.', 'woocommerce' ),
			'id'                => 'woo_address_book_billing_save_limit',
			'css'               => 'width:50px;',
			'default'           => '0',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => 0,
				'step' => 1,
			),
			'css'               => 'margin-top: -1em; width: 60px;',
		),
		array(
			'title'           => __( 'Shipping address book', 'woo-address-book' ),
			'desc'            => __( 'Enable shipping address book', 'woo-address-book' ),
			'id'              => 'woo_address_book_shipping_enable',
			'default'         => 'yes',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'start',
			'show_if_checked' => 'option',
		),
		array(
			'desc'            => __( 'Enable "Add New Address" as default selection at checkout', 'woo-address-book' ),
			'desc_tip'        => 'When checked, the shipping address book will default to "Add New Address" during checkout instead of the default address. The default address will still be used if the address book limit is set and reached.',
			'id'              => 'woo_address_book_shipping_default_to_new_address',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => '',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'            => __( 'Enable setting Shipping Address Nickname at checkout', 'woo-address-book' ),
			'desc_tip'        => 'When checked, you can set a nickname for the shipping address at checkout. When unchecked, the addresses will not have a nickname unless added in the "My account" menu.',
			'id'              => 'woo_address_book_shipping_address_nickname_checkout',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'end',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'              => __( '<strong>Shipping Address Book Limit</strong></p><p class="description">This sets the maximum number of shipping addresses that each user can save. Set to 0 for unlimited.', 'woocommerce' ),
			'id'                => 'woo_address_book_shipping_save_limit',
			'css'               => 'width:50px;',
			'default'           => '0',
			'type'              => 'number',
			'custom_attributes' => array(
				'min'  => 0,
				'step' => 1,
			),
			'css'               => 'margin-top: -1em; width: 60px;',
		),
		array(
			'title'         => __( 'Address book tools', 'woo-address-book' ),
			'desc'          => __( 'Enable import/export tool in my account', 'woo-address-book' ),
			'id'            => 'woo_address_book_tools_enable',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'start',
		),
		array(
			'desc'          => __( 'Block readonly fields from being populated by changing address during checkout', 'woo-address-book' ),
			'id'            => 'woo_address_book_block_readonly',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => '',
		),
		array(
			'desc'          => __( 'Use radio inputs instead of a select element for billing/shipping address on checkout', 'woo-address-book' ),
			'id'            => 'woo_address_book_use_radio_input',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'end',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'woo_address_book_options',
		),
	);
}

/**
 * Display the settings from the above array on the admin page
 */
function woo_address_book_settings() {
	WC_Admin_Settings::output_fields( woo_address_book_get_settings() );
}
add_action( 'woocommerce_settings_address_book', 'woo_address_book_settings' );

/**
 * Display the settings from the above array on the admin page
 */
function woo_address_book_update_woosettings() {
	WC_Admin_Settings::save_fields( woo_address_book_get_settings() );
}
add_action( 'woocommerce_update_options_address_book', 'woo_address_book_update_woosettings' );
