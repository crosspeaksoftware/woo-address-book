<?php
/**
 * Woo Address Book
 *
 * @author  Hall Internet Marketing
 * @package WooCommerce Address Book/Templates
 * @version 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$wc_address_book = new WC_Address_Book();

$customer_id  = get_current_user_id();
$address_book = $wc_address_book->get_address_book( $customer_id );

// Do not display on address edit pages.
if ( ! $type ) : ?>

	<?php

	$shipping_address = get_user_meta( $customer_id, 'shipping_address_1', true );

	// Only display if primary addresses are set and not on an edit page.
	if ( ! empty( $shipping_address ) ) :
		?>

		<hr />

		<div class="address_book">

			<h2><?php __( 'Shipping Address Book', 'wc-address-book' ); ?></h2>

			<p class="myaccount_address">
				<?php echo apply_filters( 'woocommerce_my_account_my_address_book_description', __( 'The following addresses are available during the checkout process.', 'wc-address-book' ) ); ?>
			</p>

			<?php
			if ( ! wc_ship_to_billing_address_only() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) {
				echo '<div class="col2-set addresses address-book">';
			}

			foreach ( $address_book as $name => $fields ) :

				// Prevent default shipping from displaying here.
				if ( 'shipping' === $name || 'billing' === $name ) {
					continue;
				}

				$address = apply_filters(
					'woocommerce_my_account_my_address_formatted_address',
					array(
						'first_name' => get_user_meta( $customer_id, $name . '_first_name', true ),
						'last_name'  => get_user_meta( $customer_id, $name . '_last_name', true ),
						'company'    => get_user_meta( $customer_id, $name . '_company', true ),
						'address_1'  => get_user_meta( $customer_id, $name . '_address_1', true ),
						'address_2'  => get_user_meta( $customer_id, $name . '_address_2', true ),
						'city'       => get_user_meta( $customer_id, $name . '_city', true ),
						'state'      => get_user_meta( $customer_id, $name . '_state', true ),
						'postcode'   => get_user_meta( $customer_id, $name . '_postcode', true ),
						'country'    => get_user_meta( $customer_id, $name . '_country', true ),
					),
					$customer_id,
					$name
				);

				$formatted_address = WC()->countries->get_formatted_address( $address );

				if ( $formatted_address ) :
					?>

					<div class="wc-address-book-address">
						<div class="wc-address-book-meta">
							<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'shipping/?address-book=' . $name ) ); ?>" class="wc-address-book-edit"><?php echo esc_attr__( 'Edit', 'wc-address-book' ); ?></a>
							<a id="<?php echo esc_attr( $name ); ?>" class="wc-address-book-delete"><?php echo esc_attr__( 'Delete', 'wc-address-book' ); ?></a>
							<a id="<?php echo esc_attr( $name ); ?>" class="wc-address-book-make-primary"><?php echo esc_attr__( 'Make Primary', 'wc-address-book' ); ?></a>
						</div>
						<address>
							<?php echo wp_kses( $formatted_address, array( 'br' => array() ) ); ?>
						</address>
					</div>

				<?php endif; ?>

			<?php endforeach; ?>

		</div>
	<?php endif; ?>

	<?php
	// Add link/button to the my accounts page for adding addresses.
	if ( ! empty( get_user_meta( $customer_id, 'shipping_address_1' ) ) ) {
		$wc_address_book->add_additional_address_button();
	}
	?>

<?php endif; ?>
