<?php
/**
 * WooCommerce Address Book WooCommerce Subscriptions support.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Subscriptions support.
 * Checks for non-default Address Book address and doesn't show the checkbox to update subscriptions.
 *
 * @param array<string, mixed> $address_fields Current Address fields.
 * @return array<string, mixed>
 */
function remove_address_subscription_update_box( $address_fields ) {
	if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		remove_action( 'woocommerce_after_edit_address_form_billing', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
		remove_action( 'woocommerce_after_edit_address_form_shipping', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
	}

	return $address_fields;
}
add_filter( 'woocommerce_billing_fields', __NAMESPACE__ . '\remove_address_subscription_update_box', 10, 1 );
add_filter( 'woocommerce_shipping_fields', __NAMESPACE__ . '\remove_address_subscription_update_box', 10, 1 );

/**
 * Checks if this is a subscription renewal order.
 *
 * @return bool
 */
function is_subscription_renewal() {
	if ( ! function_exists( 'wcs_get_order_type_cart_items' ) ) {
		return false;
	}
	return ( ! is_null( WC()->cart ) ) && count( wcs_get_order_type_cart_items( 'renewal' ) ) === count( WC()->cart->get_cart() );
}
