<?php
/**
 * WooCommerce Address Book Settings.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Settings;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get setting from the plugin.
 *
 * @since 3.1.0
 *
 * @param string $name The option name.
 * @param string $default_value The default value for the setting.
 * @return boolean
 */
function setting( string $name, string $default_value = 'yes' ) {
	$option = get_option( 'woo_address_book_' . $name, $default_value );
	if ( 'yes' === $option ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Add Address Book settings page to the WooCommerce settings tabs.
 *
 * @param array<string,string> $tabs The current settings tabs array.
 * @return array<string,string>
 */
function add_settings_tab( array $tabs ) {
	$tabs['address_book'] = __( 'Address Book', 'woo-address-book' );
	return $tabs;
}
add_filter( 'woocommerce_settings_tabs_array', __NAMESPACE__ . '\add_settings_tab', 50 );

/**
 * The WooCommerce Address Book settings for the admin.
 *
 * @return array<array<string,mixed>>
 */
function get_settings_fields() {
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
			'desc'            => __( 'Enable "Add New Address" as default selection at checkout', 'woo-address-book' ),
			'desc_tip'        => 'When checked, the billing address book will default to "Add New Address" during checkout instead of the default address. The default address will still be used if the address book limit is set and reached.',
			'id'              => 'woo_address_book_billing_default_to_new_address',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => '',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'            => __( 'Enable setting Billing Address Nickname at checkout', 'woo-address-book' ),
			'desc_tip'        => 'When checked, you can set a nickname for the billing address at checkout. When unchecked, the addresses will not have a nickname unless added in the "My account" menu.',
			'id'              => 'woo_address_book_billing_address_nickname_checkout',
			'default'         => 'no',
			'type'            => 'checkbox',
			'checkboxgroup'   => 'end',
			'show_if_checked' => 'yes',
		),
		array(
			'desc'              => __( '<strong>Billing Address Book Limit</strong></p><p class="description">This sets the maximum number of billing addresses that each user can save. Set to 0 for unlimited.', 'woocommerce' ),
			'id'                => 'woo_address_book_billing_save_limit',
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
 *
 * @return void
 */
function settings() {
	\WC_Admin_Settings::output_fields( get_settings_fields() );
}
add_action( 'woocommerce_settings_address_book', __NAMESPACE__ . '\settings' );

/**
 * Display the settings from the above array on the admin page
 *
 * @return void
 */
function update_settings() {
	\WC_Admin_Settings::save_fields( get_settings_fields() );
}
add_action( 'woocommerce_update_options_address_book', __NAMESPACE__ . '\update_settings' );

/**
 * Register the function to add settings link to plugin actions
 *
 * @return void
 */
function register_plugin_link() {
	$action = plugin_basename( dirname( __DIR__ ) . '/woocommerce-address-book.php' );
	add_filter( 'plugin_action_links_' . $action, __NAMESPACE__ . '\add_plugin_settings_link', 10, 1 );
}
add_action( 'admin_init', __NAMESPACE__ . '\register_plugin_link' );

/**
 * Add settings link to plugin actions
 *
 * @param array<string> $links Current links.
 * @return array<string>
 */
function add_plugin_settings_link( array $links ) {
	$settings_link = '<a href="admin.php?page=wc-settings&tab=address_book">' . esc_html__( 'Settings', 'woo-address-book' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
