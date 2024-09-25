<?php
/**
 * WooCommerce Address Book Ajax handlers.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Ajax;

use function CrossPeakSoftware\WooCommerce\AddressBook\API\get_addresses;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_current_customer;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AJAX action to delete an address.
 * Used for deleting addresses from the my-account page.
 *
 * @since 1.0.0
 */
function delete() {
	check_ajax_referer( 'woo-address-book-delete', 'nonce' );

	if ( ! isset( $_POST['name'] ) || ! isset( $_POST['type'] ) ) {
		die( 'no address passed' );
	}

	$address_name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
	$type         = sanitize_text_field( wp_unslash( $_POST['type'] ) );
	$customer     = get_current_customer( 'ajax_delete' );
	if ( ! $customer ) {
		wc_add_notice( __( 'Could not get customer.', 'woo-address-book' ), 'error' );
		wc_print_notices();
		die();
	}
	$address_book = get_address_book( $customer, $type );

	if ( $address_name === $address_book->default_key() ) {
		wc_add_notice( __( 'You cannot delete the default address, please make another address default first.', 'woo-address-book' ), 'error' );
		wc_print_notices();

		die();
	}

	$address_book->delete( $address_name );

	wc_add_notice( __( 'Address deleted successfully.', 'woo-address-book' ) );
	wc_print_notices();

	die();
}
add_action( 'wp_ajax_wc_address_book_delete', __NAMESPACE__ . '\delete' );


/**
 * AJAX action to set a default address.
 * Used for setting the default addresses from the my-account page.
 *
 * @since 1.0.0
 */
function make_default() {
	check_ajax_referer( 'woo-address-book-default', 'nonce' );

	$customer = get_current_customer( 'ajax_make_default' );

	if ( ! $customer ) {
		wc_add_notice( __( 'Could not get customer.', 'woo-address-book' ), 'error' );
		wc_print_notices();
		die();
	}

	if ( ! isset( $_POST['name'] ) || ! isset( $_POST['type'] ) ) {
		die( 'no address passed' );
	}

	$alt_address_name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
	$type             = sanitize_text_field( wp_unslash( $_POST['type'] ) );
	if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
		die( 'invalid address type' );
	}

	$address_book = get_address_book( $customer, $type );
	if ( ! $address_book->has( $alt_address_name ) ) {
		die( 'address not found' );
	}

	$address_book->set_default( $alt_address_name );

	wc_add_notice( __( 'Default address updated successfully.', 'woo-address-book' ) );
	wc_print_notices();

	die();
}
add_action( 'wp_ajax_wc_address_book_make_default', __NAMESPACE__ . '\make_default' );

/**
 * AJAX action to refresh the address at checkout.
 * Used for updating addresses dynamically on the checkout page.
 *
 * @since 1.0.0
 */
function checkout_update() {
	check_ajax_referer( 'woo-address-book-checkout', 'nonce' );

	global $woocommerce;

	if ( isset( $_POST['name'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
	} else {
		$name = 'add_new';
	}

	if ( isset( $_POST['type'] ) ) {
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
	} else {
		$type = 'shipping';
	}

	$customer = get_current_customer( 'ajax_checkout_update' );
	if ( ! $customer ) {
		wc_add_notice( __( 'Could not get customer.', 'woo-address-book' ), 'error' );
		wc_print_notices();
		die();
	}

	$response = array();

	// Get address field values.
	if ( 'add_new' === $name ) {
		if ( 'billing' === $type ) {
			$countries = $woocommerce->countries->get_allowed_countries();
		} elseif ( 'shipping' === $type ) {
			$countries = $woocommerce->countries->get_shipping_countries();
		} else {
			$countries = array();
		}
		if ( 1 === count( $countries ) ) {
			// If only one country is available, include it in the blank form.
			$response[ $type . '_country' ] = key( $countries );
		}
	} else {
		$address_book = get_address_book( $customer, $type );
		$address      = $address_book->address( $name );
		if ( ! empty( $address ) ) {
			foreach ( $address as $field => $value ) {
				$response[ $type . '_' . $field ] = $value;
			}
		}
	}

	echo wp_json_encode( $response );

	die();
}
add_action( 'wp_ajax_wc_address_book_checkout_update', __NAMESPACE__ . '\checkout_update' );
