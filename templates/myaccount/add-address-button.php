<?php
/**
 * Add Address Button - Woo Address Book
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/add-address-button.php.
 *
 * HOWEVER, on occasion Woo Address Book will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package WooCommerce Address Book/Templates
 * @version 3.1.0
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\Templates\AddAddressButton;

use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book_endpoint_url;
use function CrossPeakSoftware\WooCommerce\AddressBook\limit_saved_addresses;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template variables:
 *
 * @var string $woo_address_book_address_type 'billing' or 'shipping'.
 */

$woo_address_book_add_button_classes = 'add button wc-address-book-add-' . $woo_address_book_address_type . '-button';
$woo_address_book_under_limit        = limit_saved_addresses( $woo_address_book_address_type );
?>
<div class="wc-address-book-add-new-address add-new-address">
	<span
		class="<?php echo esc_attr( $woo_address_book_add_button_classes ); ?> disabled"
			<?php
			if ( $woo_address_book_under_limit ) {
				echo 'style="display:none"';
			}
			?>
		>
		<?php
		if ( 'billing' === $woo_address_book_address_type ) {
			echo esc_html__( 'Billing Address Book Full', 'woo-address-book' );
		} elseif ( 'shipping' === $woo_address_book_address_type ) {
			echo esc_html__( 'Shipping Address Book Full', 'woo-address-book' );
		}
		?>
	</span>
	<a
		href="<?php echo esc_url( get_address_book_endpoint_url( 'new', $woo_address_book_address_type ) ); ?>"
		class="<?php echo esc_attr( $woo_address_book_add_button_classes ); ?>"
			<?php
			if ( ! $woo_address_book_under_limit ) {
				echo 'style="display:none"';
			}
			?>
		>
		<?php
		if ( 'billing' === $woo_address_book_address_type ) {
			echo esc_html__( 'Add New Billing Address', 'woo-address-book' );
		} elseif ( 'shipping' === $woo_address_book_address_type ) {
			echo esc_html__( 'Add New Shipping Address', 'woo-address-book' );
		}
		?>
	</a>
</div>
