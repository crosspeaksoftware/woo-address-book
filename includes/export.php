<?php
/**
 * WooCommerce Address Book General.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Export;

use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book;
use function CrossPeakSoftware\WooCommerce\AddressBook\get_current_customer;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Address Book export endpoint.
 *
 * @since 3.0.0
 *
 * @return void
 */
function export_endpoint() {
	if ( ! isset( $_GET['wc-address-book-export-type'] ) ) {
		return;
	}
	if ( ! check_admin_referer( 'wc-address-book-export' ) ) {
		return;
	}
	$type = sanitize_text_field( wp_unslash( $_GET['wc-address-book-export-type'] ) );
	if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
		return;
	}

	// Make sure you can only export if the option is enabled.
	if ( ! setting( 'tools_enable', 'no' ) ) {
		return;
	}

	$customer = get_current_customer( 'export_endpoint' );
	if ( ! $customer ) {
		return;
	}

	header( 'Content-type: text/csv' );
	header( 'Cache-Control: no-store, no-cache' );
	header( 'Content-Disposition: attachment; filename="woo-address-book_' . $type . '.csv"' );

	print_addresses_as_csv( get_address_book( $customer, $type )->addresses() );

	exit;
}
// Add Address Book export endpoint.
add_action( 'init', __NAMESPACE__ . '\export_endpoint' );


/**
 * Adds a link/button to the my account for Address Book export.
 *
 * @since 3.0.0
 *
 * @param string $type - 'billing' or 'shipping'.
 * @return void
 */
function add_export_button( string $type ) {
	if ( 'billing' === $type ) :
		?>
	<div class="">
		<a href="<?php echo esc_url( get_export_endpoint_url( $type ) ); ?>" class="button button-full-width"><?php esc_html_e( 'Export all Billing Addresses', 'woo-address-book' ); ?></a>
	</div>
		<?php
	endif;

	if ( 'shipping' === $type ) :
		?>
	<div class="">
		<a href="<?php echo esc_url( get_export_endpoint_url( $type ) ); ?>" class="button button-full-width"><?php esc_html_e( 'Export all Shipping Addresses', 'woo-address-book' ); ?></a>
	</div>
		<?php
	endif;
}

/**
 * Get the Address Book export endpoint URL.
 *
 * @since 3.0.0
 *
 * @param string $type - 'billing' or 'shipping'.
 * @return string
 */
function get_export_endpoint_url( string $type ) {
	$url = wc_get_endpoint_url( 'address-book-export', '', get_permalink() );

	return wp_nonce_url( add_query_arg( array( 'wc-address-book-export-type' => $type ), $url ), 'wc-address-book-export' );
}

/**
 * Format addresses as csv file
 *
 * @since 3.0.0
 *
 * @param array $addresses_array An array of addresses.
 */
function print_addresses_as_csv( array $addresses_array ) {
	$addresses = array();
	$keys      = array();

	foreach ( $addresses_array as $address ) {
		foreach ( array_keys( $address ) as $key ) {
			if ( ! in_array( $key, $keys, true ) ) {
				$keys[] = $key;
			}
		}

		$addresses[] = $address;
	}

	$csv = fopen( 'php://output', 'w' );
	fputcsv( $csv, $keys );

	foreach ( $addresses as $row ) {
		if ( ! empty( array_filter( $row ) ) ) {
			$output = array();
			foreach ( $keys as $key ) {
				$output[] = isset( $row[ $key ] ) ? $row[ $key ] : '';
			}
			fputcsv( $csv, $output );
		}
	}
	fclose( $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
}
