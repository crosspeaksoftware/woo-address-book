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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template variables:
 *
 * @var \CrossPeakSoftware\WooCommerce\AddressBook\Address_Book $woo_address_book The current address book.
 */

$woo_address_book_add_button_classes = 'add button wc-address-book-add-' . $woo_address_book->type() . '-button';
?>
<div class="wc-address-book-add-new-address add-new-address">
	<span
		class="<?php echo esc_attr( $woo_address_book_add_button_classes ); ?> disabled"
			<?php
			if ( $woo_address_book->is_under_limit() ) {
				echo 'style="display:none"';
			}
			?>
		>
		<?php
		if ( $woo_address_book->is_billing() ) {
			echo esc_html__( 'Billing Address Book Full', 'woo-address-book' );
		} elseif ( $woo_address_book->is_shipping() ) {
			echo esc_html__( 'Shipping Address Book Full', 'woo-address-book' );
		}
		?>
	</span>
	<a
		href="<?php echo esc_url( get_address_book_endpoint_url( 'new', $woo_address_book->type() ) ); ?>"
		class="<?php echo esc_attr( $woo_address_book_add_button_classes ); ?>"
			<?php
			if ( ! $woo_address_book->is_under_limit() ) {
				echo 'style="display:none"';
			}
			?>
		>
		<?php
		if ( $woo_address_book->is_billing() ) {
			echo esc_html__( 'Add New Billing Address', 'woo-address-book' );
		} elseif ( $woo_address_book->is_shipping() ) {
			echo esc_html__( 'Add New Shipping Address', 'woo-address-book' );
		}
		?>
	</a>
</div>
