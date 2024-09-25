<?php
/**
 * WooCommerce Address Book Import.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Import;

use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book;
use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_current_customer;
use function CrossPeakSoftware\WooCommerce\AddressBook\Validation\validate_address;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Import parsed addresses
 *
 * @since 3.0.0
 *
 * @param \WC_Customer $customer The customer object.
 * @param string       $type - 'billing' or 'shipping'.
 * @param array<array> $parsed_data The data to be imported.
 *
 * @return array<string,mixed>|bool
 */
function import_addresses( \WC_Customer $customer, string $type, array $parsed_data ) {
	if ( ! $parsed_data ) {
		return false;
	}

	$import_limit_setting = get_option( 'woo_address_book_' . $type . '_save_limit', 0 );
	$address_book         = get_address_book( $customer, $type );

	$address_book->disable_automatic_save();
	$partial_import       = false;
	$address_book_changed = false;
	$counter              = 0;
	foreach ( $parsed_data as $data ) {
		if ( ! empty( $import_limit_setting ) && $import_limit_setting > 0 ) {
			$import_limit = $import_limit_setting - $address_book->count();
			if ( 0 >= $import_limit ) {
				$partial_import = true;
				break;
			}
		}

		$is_default = is_null( $address_book->default_key() );

		$address_book->add( $data, $is_default );

		$address_book_changed = true;
		++$counter;
	}
	if ( $address_book_changed ) {
		$address_book->save();
	}
	return array(
		'partial_import'       => $partial_import,
		'address_book_changed' => $address_book_changed,
		'imported_count'       => $counter,
		'address_count'        => count( $parsed_data ),
	);
}

/**
 * Handle the check for import of addresses.
 *
 * @return void
 */
function handle_address_import() {
	if ( isset( $_FILES['wc_address_book_upload_billing_csv'] ) ) {
		$type = 'billing';
	} elseif ( isset( $_FILES['wc_address_book_upload_shipping_csv'] ) ) {
		$type = 'shipping';
	} else {
		return;
	}

	if ( ! check_admin_referer( 'woo-address-book-' . $type . '-csv-import', 'woo-address-book_nonce' ) ) {
		return;
	}

	// Make sure you can only import if the option is enabled.
	if ( ! setting( 'tools_enable', 'no' ) ) {
		return;
	}

	$customer = get_current_customer( 'handle_address_import' );
	if ( ! $customer ) {
		return;
	}
	if ( ! isset( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ] ) ) {
		return;
	}
	if ( isset( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['error'] ) && 0 !== $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['error'] ) {
		wc_add_notice( __( 'Error uploading file!', 'woo-address-book' ), 'error' );
		return;
	}
	if ( ! isset( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['name'], $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['tmp_name'] ) ) {
		wc_add_notice( __( 'Error uploading file!', 'woo-address-book' ), 'error' );
		return;
	}
	if ( ! wc_is_file_valid_csv( wc_clean( wp_unslash( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['name'] ) ) ) ) {
		wc_add_notice( __( 'Invalid file type!', 'woo-address-book' ), 'error' );
		return;
	}
	$file_tmp_name = wc_clean( wp_unslash( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ]['tmp_name'] ) );
	$parsed_data   = parse_csv_file( $file_tmp_name, $type, ',' );
	if ( $parsed_data ) {
		$importable_addresses = array();
		foreach ( $parsed_data as $key => $address ) {
			if ( empty( $address[ $type . '_country' ] ) ) {
				$address[ $type . '_country' ] = WC()->countries->get_base_country();
			}
			$address_fields = WC()->countries->get_address_fields( wc_clean( $address[ $type . '_country' ] ), $type . '_' );
			$address        = validate_address( $address_fields, $address, $type );
			if ( empty( $address ) ) {
				wc_add_notice( __( 'Invalid address format.', 'woo-address-book' ), 'error' );
			}
			if ( 0 < wc_notice_count( 'error' ) ) {
				// translators: %d: line number of the address in the csv file that the validation failed on.
				wc_add_notice( sprintf( __( 'Error while validating Address #%d of the csv file.', 'woo-address-book' ), $key + 1 ), 'error' );
				return;
			}
			$importable_addresses[] = $address;
		}
		$status = import_addresses( $customer, $type, $importable_addresses );
		if ( false === $status ) {
			wc_add_notice( __( 'Error importing addresses!', 'woo-address-book' ), 'error' );
		} else {
			if ( $status['partial_import'] ) {
				if ( 'billing' === $type ) {
					// translators: %1$1d: number of addresses imported, %2$2d: total number of addresses.
					wc_add_notice( sprintf( __( 'Imported %1$1d of %2$2d billing addresses. Billing Address Book is full!', 'woo-address-book' ), $status['imported_count'], $status['address_count'] ), 'notice' );
				} elseif ( 'shipping' === $type ) {
					// translators: %1$1d: number of addresses imported, %2$2d: total number of addresses.
					wc_add_notice( sprintf( __( 'Imported %1$1d of %2$2d shipping addresses. Shipping Address Book is full!', 'woo-address-book' ), $status['imported_count'], $status['address_count'] ), 'notice' );
				}
			} elseif ( 'billing' === $type ) {
					// translators: %1$d: number of addresses imported.
					wc_add_notice( sprintf( __( 'Imported %1d billing addresses successfully.', 'woo-address-book' ), $status['imported_count'] ), 'success' );
			} elseif ( 'shipping' === $type ) {
				// translators: %1$d: number of addresses imported.
				wc_add_notice( sprintf( __( 'Imported %1d shipping addresses successfully.', 'woo-address-book' ), $status['imported_count'] ), 'success' );
			}
			// Redirect to the address book page.
			wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}
}
// Handle csv import.
add_action( 'init', __NAMESPACE__ . '\handle_address_import' );


/**
 * Parse uploaded csv file
 *
 * @since 3.0.0
 *
 * @param string $file The uploaded file to be parsed.
 * @param string $type The type, 'billing' or 'shipping'.
 * @param string $delimiter The csv delimiter. Defaults to ','.
 * @return array<array<string,string>>|false
 */
function parse_csv_file( string $file, string $type, string $delimiter = ',' ) {
	$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	if ( false !== $handle ) {
		$first_row = true;
		$csv       = array();
		$keys      = array();

		while ( ( $data = fgetcsv( $handle, 10000, $delimiter ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( $first_row ) {
				// Values in the first row are used as key names.
				$keys      = array_values( $data );
				$first_row = false;
			} else {
				$row = array();
				foreach ( $keys as $i => $key ) {
					$row[ $type . '_' . $key ] = $data[ $i ] ?? '';
				}
				$csv[] = $row;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $csv;
	}
	return false;
}
