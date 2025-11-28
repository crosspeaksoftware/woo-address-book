<?php
/**
 * Woo Address Book
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-address-book.php.
 *
 * HOWEVER, on occasion Woo Address Book will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Address Book for WooCommerce/Templates
 * @version 1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$wc_address_book = WC_Address_Book::get_instance();

$woo_address_book_customer_id           = get_current_user_id();
$woo_address_book_billing_address_book  = $wc_address_book->get_address_book( $woo_address_book_customer_id, 'billing' );
$woo_address_book_shipping_address_book = $wc_address_book->get_address_book( $woo_address_book_customer_id, 'shipping' );

// Do not display on address edit pages.
if ( ! $type ) {
	if ( $wc_address_book->get_wcab_option( 'billing_enable' ) === true ) {
		$woo_address_book_billing_address = get_user_meta( $woo_address_book_customer_id, 'billing_address_1', true );

		// Hide the billing address book if there are no addresses to show and no ability to add new ones.
		$count_section = count( $woo_address_book_billing_address_book );
		$save_limit    = get_option( 'woo_address_book_billing_save_limit', 0 );

		if ( 1 == $save_limit && $count_section <= 1 ) {
			$hide_billing_address_book = true;
		} else {
			$hide_billing_address_book = false;
		}

		// Only display if primary addresses are set and not on an edit page.
		if ( ! empty( $woo_address_book_billing_address ) && ! $hide_billing_address_book ) {
			?>

			<div class="address_book billing_address_book" data-addresses='<?php echo $count_section; ?>' data-limit='<?php echo $save_limit; ?>'>
				<header>
					<h3><?php esc_html_e( 'Billing Address Book', 'woo-address-book' ); ?></h3>
					<?php
					// Add link/button to the my accounts page for adding addresses.
					$wc_address_book->add_additional_address_button( 'billing' );
					?>
				</header>

				<p class="myaccount_address"><?php echo esc_html( apply_filters( 'woocommerce_my_account_my_address_book_description', __( 'The following addresses are available during the checkout process.', 'woo-address-book' ) ) ); ?></p>
				<div class="col2-set addresses address-book">
					<?php

					foreach ( $woo_address_book_billing_address_book as $woo_address_book_name => $woo_address_book_fields ) {
						// Prevent default billing from displaying here.
						if ( 'billing' === $woo_address_book_name ) {
							continue;
						}

						$woo_address_book_address = apply_filters(
							'woocommerce_my_account_my_address_formatted_address',
							array(
								'first_name' => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_first_name', true ),
								'last_name'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_last_name', true ),
								'company'    => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_company', true ),
								'address_1'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_address_1', true ),
								'address_2'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_address_2', true ),
								'city'       => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_city', true ),
								'state'      => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_state', true ),
								'postcode'   => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_postcode', true ),
								'country'    => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_country', true ),
							),
							$woo_address_book_customer_id,
							$woo_address_book_name
						);

						$woo_address_book_formatted_address = WC()->countries->get_formatted_address( $woo_address_book_address );

						if ( $woo_address_book_formatted_address ) {
							?>

							<div class="wc-address-book-address">
								<div class="wc-address-book-meta">
									<a href="<?php echo esc_url( $wc_address_book->get_address_book_endpoint_url( $woo_address_book_name, 'billing' ) ); ?>" class="wc-address-book-edit"><?php echo esc_attr__( 'Edit', 'woo-address-book' ); ?></a>
									<a id="<?php echo esc_attr( $woo_address_book_name ); ?>" class="wc-address-book-delete"><?php echo esc_attr__( 'Delete', 'woo-address-book' ); ?></a>
									<a id="<?php echo esc_attr( $woo_address_book_name ); ?>" class="wc-address-book-make-primary"><?php echo esc_attr__( 'Make Primary', 'woo-address-book' ); ?></a>
								</div>
								<address>
									<?php echo wp_kses( $woo_address_book_formatted_address, array( 'br' => array() ) ); ?>
								</address>
							</div>

							<?php
						}
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	if ( $wc_address_book->get_wcab_option( 'shipping_enable' ) === true ) {
		$woo_address_book_shipping_address = get_user_meta( $woo_address_book_customer_id, 'shipping_address_1', true );

		// Hide the billing address book if there are no addresses to show and no ability to add new ones.
		$count_section = count( $woo_address_book_shipping_address_book );
		$save_limit    = intval( get_option( 'woo_address_book_shipping_save_limit', 0 ) );

		if ( 1 == $save_limit && $count_section <= 1 ) {
			$hide_shipping_address_book = true;
		} else {
			$hide_shipping_address_book = false;
		}

		// Only display if primary addresses are set and not on an edit page.
		if ( ! empty( $woo_address_book_shipping_address ) && ! $hide_shipping_address_book ) {
			?>

			<div class="address_book shipping_address_book" data-addresses='<?php echo esc_attr( $count_section ); ?>' data-limit='<?php echo esc_attr( $save_limit ); ?>'>

				<header>
					<h3><?php esc_html_e( 'Shipping Address Book', 'woo-address-book' ); ?></h3>
					<?php
					// Add link/button to the my accounts page for adding addresses.
					$wc_address_book->add_additional_address_button( 'shipping' );
					?>
				</header>

				<p class="myaccount_address">
					<?php echo esc_html( apply_filters( 'woocommerce_my_account_my_address_book_description', __( 'The following addresses are available during the checkout process.', 'woo-address-book' ) ) ); ?>
				</p>

				<?php
				if ( ! wc_ship_to_billing_address_only() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) {
					echo '<div class="col2-set addresses address-book">';
				}

				foreach ( $woo_address_book_shipping_address_book as $woo_address_book_name => $woo_address_book_fields ) {

					// Prevent default shipping from displaying here.
					if ( 'shipping' === $woo_address_book_name ) {
						continue;
					}

					$woo_address_book_address = apply_filters(
						'woocommerce_my_account_my_address_formatted_address',
						array(
							'first_name' => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_first_name', true ),
							'last_name'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_last_name', true ),
							'company'    => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_company', true ),
							'address_1'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_address_1', true ),
							'address_2'  => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_address_2', true ),
							'city'       => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_city', true ),
							'state'      => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_state', true ),
							'postcode'   => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_postcode', true ),
							'country'    => get_user_meta( $woo_address_book_customer_id, $woo_address_book_name . '_country', true ),
						),
						$woo_address_book_customer_id,
						$woo_address_book_name
					);

					$woo_address_book_formatted_address = WC()->countries->get_formatted_address( $woo_address_book_address );

					if ( $woo_address_book_formatted_address ) {
						?>
						<div class="wc-address-book-address">
							<div class="wc-address-book-meta">
								<a href="<?php echo esc_url( $wc_address_book->get_address_book_endpoint_url( $woo_address_book_name, 'shipping' ) ); ?>" class="wc-address-book-edit"><?php echo esc_attr__( 'Edit', 'woo-address-book' ); ?></a>
								<a id="<?php echo esc_attr( $woo_address_book_name ); ?>" class="wc-address-book-delete"><?php echo esc_attr__( 'Delete', 'woo-address-book' ); ?></a>
								<a id="<?php echo esc_attr( $woo_address_book_name ); ?>" class="wc-address-book-make-primary"><?php echo esc_attr__( 'Make Primary', 'woo-address-book' ); ?></a>
							</div>
							<address>
								<?php echo wp_kses( $woo_address_book_formatted_address, array( 'br' => array() ) ); ?>
							</address>
						</div>
						<?php
					}
				}

				if ( ! wc_ship_to_billing_address_only() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) {
					echo '</div>';
				}
				?>
			</div>
			<?php
		}
	}
}
