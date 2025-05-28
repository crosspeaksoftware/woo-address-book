<?php
/**
 * WooCommerce Address Book.
 *
 * @class    WC_Address_Book
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Validation;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Validate the address fields.
 *
 * @param array<string,array<string,mixed>> $address The set of WooCommerce Address Fields.
 * @param array<string,string>              $values The set of values to validate.
 * @param string                            $address_type Address type to load, billing or shipping.
 * @return array<string,string>
 */
function validate_address( $address, $values, $address_type ) {
	$address_values = array();
	foreach ( $address as $key => $field ) {
		if ( ! isset( $field['type'] ) ) {
			$field['type'] = 'text';
		}

		// Get Value.
		if ( 'checkbox' === $field['type'] ) {
			$value = (int) isset( $values[ $key ] );
		} else {
			$value = isset( $values[ $key ] ) ? wc_clean( wp_unslash( $values[ $key ] ) ) : '';
		}

		/**
		 * Hook to allow modification of value.
		 *
		 * @since 3.0.0
		 * @param mixed $value
		 */
		$value = apply_filters( 'woocommerce_process_myaccount_field_' . $key, $value );

		// Validation: Required fields.
		if ( ! empty( $field['required'] ) && empty( $value ) ) {
			/* translators: %s: Field name. */
			wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ), 'error', array( 'id' => $key ) );
		}

		if ( ! empty( $value ) ) {
			// Validation and formatting rules.
			if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
				foreach ( $field['validate'] as $rule ) {
					switch ( $rule ) {
						case 'postcode':
							$country = wc_clean( wp_unslash( $values[ $address_type . '_country' ] ) );
							$value   = wc_format_postcode( $value, $country );

							if ( '' !== $value && ! \WC_Validation::is_postcode( $value, $country ) ) {
								switch ( $country ) {
									case 'IE':
										$postcode_validation_notice = __( 'Please enter a valid Eircode.', 'woocommerce' );
										break;
									default:
										$postcode_validation_notice = __( 'Please enter a valid postcode / ZIP.', 'woocommerce' );
								}
								wc_add_notice( $postcode_validation_notice, 'error' );
							}
							break;
						case 'phone':
							if ( '' !== $value && ! \WC_Validation::is_phone( $value ) ) {
								/* translators: %s: Phone number. */
								wc_add_notice( sprintf( __( '%s is not a valid phone number.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
							}
							break;
						case 'email':
							$value = strtolower( $value );

							if ( ! is_email( $value ) ) {
								/* translators: %s: Email address. */
								wc_add_notice( sprintf( __( '%s is not a valid email address.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
							}
							break;
					}
				}
			}
		}

		$book_key                    = substr( $key, strlen( $address_type . '_' ) );
		$address_values[ $book_key ] = $value;
	}
	return $address_values;
}
