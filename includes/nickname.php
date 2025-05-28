<?php
/**
 * WooCommerce Address Book Nickname field.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Nickname;

use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_current_customer;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Add Address Nickname fields to billing address fields.
 *
 * @param array<string, mixed> $address_fields Current Address fields.
 * @return array<string, mixed>
 *
 * @since 1.8.0
 */
function add_billing_address_nickname_field( array $address_fields ) {
	if ( ! isset( $address_fields['billing_address_nickname'] ) ) {
		$address_fields['billing_address_nickname'] = array(
			'label'        => __( 'Address nickname', 'woo-address-book' ),
			'required'     => false,
			'class'        => array( 'form-row-wide' ),
			'autocomplete' => 'given-name',
			'priority'     => -1,
			'value'        => '',
			'description'  => __( 'Will help you identify your addresses easily.', 'woo-address-book' ),
			'validate'     => array( 'address-nickname' ),
		);
	}

	return $address_fields;
}
add_filter( 'woocommerce_billing_fields', __NAMESPACE__ . '\add_billing_address_nickname_field', 10, 1 );


/**
 * Add Address Nickname fields to shipping address fields.
 *
 * @param array<string, mixed> $address_fields Current Address fields.
 * @return array<string, mixed>
 *
 * @since 1.8.0
 */
function add_shipping_address_nickname_field( array $address_fields ) {
	if ( ! isset( $address_fields['shipping_address_nickname'] ) ) {
		$address_fields['shipping_address_nickname'] = array(
			'label'        => __( 'Address nickname', 'woo-address-book' ),
			'required'     => false,
			'class'        => array( 'form-row-wide' ),
			'autocomplete' => 'given-name',
			'priority'     => -1,
			'value'        => '',
			'description'  => __( 'Will help you identify your addresses easily. Suggested nicknames: Home, Work...', 'woo-address-book' ),
			'validate'     => array( 'address-nickname' ),
		);
	}

	return $address_fields;
}
add_filter( 'woocommerce_shipping_fields', __NAMESPACE__ . '\add_shipping_address_nickname_field', 10, 1 );

/**
 * Perform validation on the Billing Address Nickname field.
 *
 * @param string $new_nickname The nickname the user input.
 * @return string|bool
 *
 * @since 1.8.0
 */
function validate_billing_address_nickname( string $new_nickname ) {
	return validate_address_nickname( $new_nickname, 'billing' );
}
add_filter( 'woocommerce_process_myaccount_field_billing_address_nickname', __NAMESPACE__ . '\validate_billing_address_nickname', 10, 1 );

/**
 * Perform validation on the Shipping Address Nickname field.
 *
 * @param string $new_nickname The nickname the user input.
 * @return string|bool
 *
 * @since 1.8.0
 */
function validate_shipping_address_nickname( string $new_nickname ) {
	return validate_address_nickname( $new_nickname, 'shipping' );
}
add_filter( 'woocommerce_process_myaccount_field_shipping_address_nickname', __NAMESPACE__ . '\validate_shipping_address_nickname', 10, 1 );

/**
 * Perform validation on the Address Nickname field.
 *
 * @param string $new_nickname The nickname the user input.
 * @param string $type billing or shipping.
 *
 * @since 3.0.0
 *
 * @return string|bool
 */
function validate_address_nickname( string $new_nickname, string $type ) {
	$current_address_name = $type;
	if ( ! empty( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_address_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$customer = get_current_customer( 'validate_address_nickname' );

	if ( ! $customer ) {
		return $new_nickname;
	}

	$address_book = get_address_book( $customer, $type );
	if ( $current_address_name === $type && ! empty( $address_book->default_key() ) ) {
		$current_address_name = $address_book->default_key();
	}

	if ( $address_book->count() > 0 ) {
		foreach ( $address_book->addresses() as $address_name => $address ) {
			if ( $current_address_name !== $address_name ) {
				$address_nickname = $address['address_nickname'];
				if ( ! empty( $new_nickname ) && sanitize_title( $address_nickname ) === sanitize_title( $new_nickname ) ) {
					// address nickname should be unique.
					wc_add_notice( __( 'Address nickname should be unique, another address is using the nickname.', 'woo-address-book' ), 'error' );
					$new_nickname = false;
					break;
				}
			}
		}
	}

	return $new_nickname;
}

/**
 * Perform the replacement of the localized format with the data.
 *
 * @param array<string, string> $address Address Formats.
 * @param array<string, string> $args Address Data.
 * @return array<string, string>
 */
function address_nickname_field_replacement( array $address, array $args ) {
	$address['{address_nickname}'] = '';

	if ( ! empty( $args['address_nickname'] ) ) {
		$address['{address_nickname}'] = $args['address_nickname'];
	}

	return $address;
}
add_filter( 'woocommerce_formatted_address_replacements', __NAMESPACE__ . '\address_nickname_field_replacement', 10, 2 );

/**
 * Prefix address formats with the address nickname.
 *
 * @param array<string, string> $formats All of the country formats.
 * @return array<string, string>
 */
function address_nickname_localization_format( array $formats ) {
	foreach ( $formats as $iso_code => $format ) {
		$formats[ $iso_code ] = "{address_nickname}\n" . $format;
	}

	return $formats;
}
add_filter( 'woocommerce_localisation_address_formats', __NAMESPACE__ . '\address_nickname_localization_format', -10 );

/**
 * Get the address nickname to add it to the formatted address data.
 *
 * @param array<string, mixed> $fields Address fields.
 * @param int                  $user_id Customer to get address for.
 * @param string               $type Which address to get.
 * @return array<string, mixed>
 */
function get_address_nickname( array $fields, int $user_id, string $type ) {
	if ( isset( $fields['address_nickname'] ) ) {
		return $fields;
	}
	$customer     = new \WC_Customer( $user_id );
	$address_book = get_address_book( $customer, $type );
	$address_name = $type;
	if ( ! empty( $address_book->default_key() ) ) {
		$address_name = $address_book->default_key();
	}

	$address                    = $address_book->address( $address_name );
	$fields['address_nickname'] = $address['address_nickname'] ?? '';

	return $fields;
}
add_filter( 'woocommerce_my_account_my_address_formatted_address', __NAMESPACE__ . '\get_address_nickname', 10, 3 );

/**
 * Don't show Address Nickname field on the checkout if the option is configured not to.
 *
 * @param array<string, mixed> $fields Checkout fields.
 * @return array<string, mixed>
 */
function remove_nickname_field_from_checkout( array $fields ) {
	if ( isset( $fields['shipping']['shipping_address_nickname'] ) && ! setting( 'shipping_address_nickname_checkout', 'no' ) ) {
		unset( $fields['shipping']['shipping_address_nickname'] );
	}

	if ( isset( $fields['billing']['billing_address_nickname'] ) && ! setting( 'billing_address_nickname_checkout', 'no' ) ) {
		unset( $fields['billing']['billing_address_nickname'] );
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', __NAMESPACE__ . '\remove_nickname_field_from_checkout' );
