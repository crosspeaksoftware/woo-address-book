<?php
/**
 * WooCommerce Address Book.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook;

use function CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting;
use function CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions\is_subscription_renewal;
use function CrossPeakSoftware\WooCommerce\AddressBook\Validation\validate_address;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const PLUGIN_VERSION = '3.0.2.12';

/**
 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
 *
 * @param string $type - 'billing' or 'shipping'.
 *
 * @return void
 */
function add_additional_address_button( string $type ) {
	$under_limit = limit_saved_addresses( $type );

	$add_button_classes = 'add button wc-address-book-add-' . $type . '-button';

	/**
	 * Filter to override if the add new address button should be shown.
	 *
	 * @since 2.0.0
	 */
	if ( apply_filters( 'wc_address_book_show_' . $type . '_address_button', true ) ) :
		?>
	<div class="wc-address-book-add-new-address add-new-address">
		<span
			class="<?php echo esc_attr( $add_button_classes ); ?> disabled"
				<?php
				if ( $under_limit ) {
					echo 'style="display:none"';
				}
				?>
			>
			<?php
			if ( 'billing' === $type ) {
				echo esc_html__( 'Billing Address Book Full', 'woo-address-book' );
			} elseif ( 'shipping' === $type ) {
				echo esc_html__( 'Shipping Address Book Full', 'woo-address-book' );
			}
			?>
		</span>
		<a
			href="<?php echo esc_url( get_address_book_endpoint_url( 'new', $type ) ); ?>"
			class="<?php echo esc_attr( $add_button_classes ); ?>"
				<?php
				if ( ! $under_limit ) {
					echo 'style="display:none"';
				}
				?>
			>
			<?php
			if ( 'billing' === $type ) {
				echo esc_html__( 'Add New Billing Address', 'woo-address-book' );
			} elseif ( 'shipping' === $type ) {
				echo esc_html__( 'Add New Shipping Address', 'woo-address-book' );
			}
			?>
		</a>
	</div>
		<?php
endif;
}

/**
 * Removes the link/button to add new addresses, if over the save limit in the settings.
 *
 * @param string $type - 'billing' or 'shipping'.
 *
 * @return boolean
 */
function limit_saved_addresses( string $type ) {
	$save_limit = get_option( 'woo_address_book_' . $type . '_save_limit', 0 );
	if ( empty( $save_limit ) ) {
		return true;
	}
	$customer = get_current_customer( 'limit_saved_addresses' );
	if ( ! $customer ) {
		return true;
	}
	$address_book = get_address_book( $customer, $type );
	if ( $address_book->count() < $save_limit ) {
		return true;
	}
	return false;
}

/**
 * Get the address book edit endpoint URL.
 *
 * @param string $address_book Address book name.
 * @param string $type - 'billing' or 'shipping'.
 *
 * @return string
 */
function get_address_book_endpoint_url( string $address_book, string $type ) {
	$url = wc_get_endpoint_url( 'edit-address', $type, get_permalink() );
	return add_query_arg( 'address-book', $address_book, $url );
}

/**
 * Replace My Address with the Address Book to My Account Menu.
 *
 * @param array $items - An array of menu items.
 * @return array
 */
function add_to_menu( array $items ) {
	foreach ( $items as $key => $value ) {
		if ( 'edit-address' === $key ) {
			$items[ $key ] = __( 'Address book', 'woo-address-book' );
		}
	}

	return $items;
}
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\add_to_menu', 10 );

/**
 * Adds Address Book Content.
 *
 * @param string $type - The type of address.
 *
 * @return void
 */
function address_page( string $type ) {
	// Only show template on my account page, not edit billing or shipping address.
	if ( empty( $type ) ) {
		wc_get_template( 'myaccount/my-address-book.php', array(), '', plugin_dir_path( __DIR__ ) . 'templates/' );
	}
}
add_action( 'woocommerce_account_edit-address_endpoint', __NAMESPACE__ . '\address_page', 20 );

/**
 * Returns an array of the customer's addresses with field values.
 *
 * @since 1.0.0
 *
 * @param \WC_Customer $customer The Customer object.
 * @param string       $type - 'billing' or 'shipping'.
 * @return Address_Book
 */
function get_address_book( \WC_Customer $customer, string $type ) {
	return new Address_Book( $customer, $type );
}

/**
 * The the address specified for the customer.
 *
 * Get the address from woocommerce formatted addresses.
 * Gets the address from the 2.x version of the plugin for backwards compatibility.
 *
 * @param \WC_Customer $customer The Customer object.
 * @param string       $key The key of the address.
 * @param string       $type The type of address (billing or shipping).
 * @return array
 */
function get_woo_address( \WC_Customer $customer, string $key, string $type ) {
	// Fetch address from WooCommerce customer meta.
	$address_fields = WC()->countries->get_address_fields( ( new \WC_Countries() )->get_base_country(), $type . '_' );

	$address_keys = array_keys( $address_fields );
	$address_keys = array_merge( $address_keys, array( $type . '_address_nickname' ) );

	$address = array();

	foreach ( $address_keys as $field ) {
		// Remove the default name so the custom ones can be added.
		$custom_field      = $key . substr( $field, strlen( $type ) );
		$address_field_key = substr( $field, strlen( $type ) + 1 );

		$function = 'get_' . ltrim( $custom_field, '_' );

		if ( is_callable( array( $customer, $function ) ) ) {
			$address[ $address_field_key ] = $customer->{$function}();
		} else {
			$address[ $address_field_key ] = $customer->get_meta( $custom_field );
		}
	}

	return $address;
}

/**
 * Handles deprecated addresses from prior to 3.0 when formatting for legacy templates.
 *
 * @param array  $fields Address fields.
 * @param int    $customer_id Customer ID.
 * @param string $address_name Address name.
 * @return array
 */
function legacy_address_formatting( array $fields, $customer_id, $address_name ) {
	if ( empty( array_filter( $fields ) ) ) {
		if ( str_starts_with( $address_name, 'shipping' ) ) {
			$type = 'shipping';
		} elseif ( str_starts_with( $address_name, 'billing' ) ) {
			$type = 'billing';
		} else {
			$type = null;
		}
		if ( $type ) {
			$address_book = get_address_book( new \WC_Customer( $customer_id ), $type );
			$fields       = $address_book->address( $address_name );
		}
	}
	// Check if any fields are arrays that can't be displayed and removed those.
	foreach ( $fields as $key => $value ) {
		if ( is_array( $value ) ) {
			unset( $fields[ $key ] );
		}
	}
	return $fields;
}
add_filter( 'woocommerce_my_account_my_address_formatted_address', __NAMESPACE__ . '\legacy_address_formatting', 10, 3 );

/**
 * Adds the address book select to the checkout page.
 *
 * @since 1.0.0
 *
 * @param array $fields An array of WooCommerce Address fields.
 * @return array
 */
function checkout_address_select_field( array $fields ) {
	if ( is_user_logged_in() ) {
		$customer = get_current_customer( 'checkout_address_select_field' );
		if ( empty( $customer ) ) {
			return $fields;
		}
		foreach ( $fields as $type => $address_fields ) {
			if ( ( 'billing' === $type && setting( 'billing_enable' ) === true ) || ( 'shipping' === $type && setting( 'shipping_enable' ) === true ) ) {
				$address_book = get_address_book( $customer, $type );
				$under_limit  = limit_saved_addresses( $type );

				$select_type = 'select';
				if ( setting( 'use_radio_input', 'no' ) === true ) {
					$select_type = 'radio';
				}

				$address_selector                            = array();
				$address_selector[ $type . '_address_book' ] = array(
					'type'     => $select_type,
					'class'    => array( 'form-row-wide', 'address_book' ),
					'label'    => __( 'Address Book', 'woo-address-book' ),
					'order'    => -1,
					'priority' => -1,
				);

				if ( $address_book->count() > 0 ) {
					$is_subscription_renewal = is_subscription_renewal();

					if ( $is_subscription_renewal ) {
						$default_to_new_address = false;
					} else {
						$default_to_new_address = setting( $type . '_default_to_new_address', 'no' );
					}

					foreach ( $address_book->addresses() as $name => $address ) {
						$label = address_select_label( $address );
						if ( ! empty( $label ) ) {
							$address_selector[ $type . '_address_book' ]['options'][ $name ] = $label;
						}
					}

					if ( $is_subscription_renewal ) {
						$address_selector[ $type . '_address_book' ]['class'][] = 'wc-address-book-subscription-renewal';
					}

					if ( $under_limit ) {
						$address_selector[ $type . '_address_book' ]['options']['add_new'] = __( 'Add New Address', 'woo-address-book' );
					}

					if ( $default_to_new_address ) {
						$address_selector[ $type . '_address_book' ]['default'] = 'add_new';
					} else {
						$address_selector[ $type . '_address_book' ]['default'] = $address_book->default_key();
					}

					$fields[ $type ] = $address_selector + $fields[ $type ];
				}
			}
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', __NAMESPACE__ . '\checkout_address_select_field', 10000, 1 );

/**
 * Adds the address book select to the checkout page.
 *
 * @since 1.0.0
 *
 * @param array $address An array of WooCommerce Address data.
 * @return string
 */
function address_select_label( array $address ) {
	$label = '';

	if ( ! empty( $address['address_nickname'] ) ) {
		$label .= $address['address_nickname'] . ': ';
	}

	if ( ! empty( $address['first_name'] ) ) {
		$label .= $address['first_name'];
	}
	if ( ! empty( $address['last_name'] ) ) {
		if ( ! empty( $label ) ) {
			$label .= ' ';
		}
		$label .= $address['last_name'];
	}
	if ( ! empty( $address['address_1'] ) ) {
		if ( ! empty( $label ) ) {
			$label .= ', ';
		}
		$label .= $address['address_1'];
	}
	if ( ! empty( $address['address_2'] ) ) {
		if ( ! empty( $label ) ) {
			$label .= ', ';
		}
		$label .= $address['address_2'];
	}
	if ( ! empty( $address['city'] ) ) {
		if ( ! empty( $label ) ) {
			$label .= ', ';
		}
		$label .= $address['city'];
	}
	if ( ! empty( $address['state'] ) ) {
		if ( ! empty( $label ) ) {
			$label .= ', ';
		}
		$label .= $address['state'];
	}

	/**
	 * Modify the address book select label.
	 *
	 * @since 2.0.0
	 * @param string $label The address book select label.
	 * @param array $address The address data.
	 */
	return apply_filters( 'wc_address_book_address_select_label', $label, $address );
}

/**
 * Update the customer data with the information entered on checkout.
 *
 * @since 1.0.0
 *
 * @param boolean      $update_customer_data - Toggles whether Woo should update customer data on checkout. This plugin overrides that function entirely.
 * @param \WC_Checkout $checkout_object - An object of the checkout fields and values.
 *
 * @return boolean
 */
function woocommerce_checkout_update_customer_data( bool $update_customer_data, \WC_Checkout $checkout_object ) {
	// Nonce is checked in the checkout before this function is run on the filter.
	$billing_name  = isset( $_POST['billing_address_book'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_address_book'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$shipping_name = isset( $_POST['shipping_address_book'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address_book'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	/**
	 * Provides an opportunity to modify the customer ID associated with the current checkout process.
	 *
	 * @since 3.0.0 or earlier
	 *
	 * @param int $user_id The current user's ID (this may be 0 if no user is logged in).
	 */
	$customer_id             = apply_filters( 'woocommerce_checkout_customer_id', current_user_id( 'woocommerce_checkout_update_customer_data' ) );
	$update_customer_data    = false;
	$ignore_shipping_address = true;

	if ( isset( $_POST['ship_to_different_address'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ignore_shipping_address = false;
	}

	$customer = new \WC_Customer( $customer_id );

	if ( ! setting( 'billing_enable' ) ) {
		$billing_name = 'billing';
	}

	if ( ! setting( 'shipping_enable' ) ) {
		$shipping_name = 'shipping';
	}

	$data = $checkout_object->get_posted_data();

	if ( ! empty( $data['billing_first_name'] ) && '' === $customer->get_first_name() ) {
		$customer->set_first_name( $data['billing_first_name'] );
	}

	if ( ! empty( $data['billing_last_name'] ) && '' === $customer->get_last_name() ) {
		$customer->set_last_name( $data['billing_last_name'] );
	}

	// If the display name is an email, update to the user's full name.
	if ( is_email( $customer->get_display_name() ) ) {
		$customer->set_display_name( $customer->get_first_name() . ' ' . $customer->get_last_name() );
	}

	$address_book_billing_address  = array();
	$address_book_shipping_address = array();

	foreach ( $data as $key => $value ) {
		// Prevent address book fields from being written to the DB.
		if ( in_array( $key, array( 'shipping_address_book', 'billing_address_book' ), true ) ) {
			continue;
		}

		// Prevent shipping keys from updating when ignoring shipping address.
		if ( 0 === stripos( $key, 'shipping_' ) && $ignore_shipping_address ) {
			continue;
		}

		if ( 0 === stripos( $key, 'shipping_' ) && 'shipping_method' !== $key ) {
			// trim off the shipping_ prefix.
			$address_book_shipping_address[ substr( $key, 9 ) ] = $value;
		} elseif ( 0 === stripos( $key, 'billing_' ) ) {
			// trim off the billing_ prefix.
			$address_book_billing_address[ substr( $key, 8 ) ] = $value;
		}

		// Store custom meta.
		if ( 'shipping' !== $shipping_name && 0 === stripos( $key, 'shipping_' ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// Don't store custom shipping to meta.
		} elseif ( 'billing' !== $billing_name && 0 === stripos( $key, 'billing_' ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif
			// Don't store custom billing to meta.
		} elseif ( is_callable( array( $customer, "set_{$key}" ) ) ) {
			// Use setters where available.
			$customer->{"set_{$key}"}( $value );

			// Store custom fields prefixed with shipping_ or billing_.
		} elseif ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) ) {
			$customer->update_meta_data( $key, $value );
		}
	}
	if ( ! empty( $address_book_billing_address ) && setting( 'billing_enable' ) ) {
		$billing_address_book = get_address_book( $customer, 'billing' );
		if ( 'add_new' === $billing_name || false === $billing_name || ! $billing_address_book->has( $billing_name ) ) {
			$billing_address_book->add( $address_book_billing_address );
		} else {
			$address = $billing_address_book->address( $billing_name );
			$address = array_merge( $address, $address_book_billing_address );
			$billing_address_book->update( $billing_name, $address );
		}
	}
	if ( ! empty( $address_book_shipping_address ) && setting( 'shipping_enable' ) ) {
		$shipping_address_book = get_address_book( $customer, 'shipping' );
		if ( 'add_new' === $shipping_name || false === $shipping_name || ! $shipping_address_book->has( $shipping_name ) ) {
			$shipping_address_book->add( $address_book_shipping_address );
		} else {
			$address = $shipping_address_book->address( $shipping_name );
			$address = array_merge( $address, $address_book_shipping_address );
			$shipping_address_book->update( $shipping_name, $address );
		}
	}

	/**
	 * Action hook to adjust customer before save.
	 *
	 * @since 3.0.0
	 */
	do_action( 'woocommerce_checkout_update_customer', $customer, $data );

	$customer->save();

	return $update_customer_data;
}
add_filter( 'woocommerce_checkout_update_customer_data', __NAMESPACE__ . '\woocommerce_checkout_update_customer_data', 10, 2 );

/**
 * Replace the WooCommerce Save Address form handler.
 *
 * Logic copied from woocommerce/includes/class-wc-form-handler.php
 * save_address() method.
 *
 * @return void
 */
function save_address_form_handler() {
	if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) {
		return;
	}

	$nonce_value = wc_get_var( $_REQUEST['woocommerce-edit-address-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

	if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-edit_address' ) ) {
		return;
	}

	$is_default        = false;
	$address_book_name = null;
	if ( isset( $_GET['address-book'] ) ) {
		$address_book_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
		if ( 'new' === $address_book_name ) {
			$address_book_name = null;
		}
	} else {
		// Not an address book address, so load default address.
		$is_default = true;
	}

	// Change post action so that the WooCommerce save_address doesn't trigger.
	$_POST['action'] = 'edit_address_book';

	wc_nocache_headers();

	$customer = get_current_customer( 'save_address_form_handler' );

	if ( ! $customer ) {
		return;
	}

	global $wp;

	$address_type = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';

	if ( ! isset( $_POST[ $address_type . '_country' ] ) ) {
		return;
	}

	$address = WC()->countries->get_address_fields( wc_clean( wp_unslash( $_POST[ $address_type . '_country' ] ) ), $address_type . '_' );

	$address_values = validate_address( $address, $_POST, $address_type );

	/**
	 * Hook: woocommerce_after_save_address_validation.
	 *
	 * Allow developers to add custom validation logic and throw an error to prevent save.
	 *
	 * @since 3.6.0
	 * @param int          $user_id User ID being saved.
	 * @param string       $address_type Type of address; 'billing' or 'shipping'.
	 * @param array        $address The address fields.
	 * @param \WC_Customer $customer The customer object being saved.
	 */
	do_action( 'woocommerce_after_save_address_validation', $customer->get_id(), $address_type, $address, $customer );

	if ( 0 < wc_notice_count( 'error' ) ) {
		return;
	}

	$address_book = get_address_book( $customer, $address_type );

	if ( $is_default ) {
		$address_book_name = $address_book->default_key();
	}

	if ( is_null( $address_book_name ) ) {
		$address_book->add( $address_values, $is_default );
		wc_add_notice( __( 'Address added successfully.', 'woo-address-book' ) );
	} else {
		$address_book->update( $address_book_name, $address_values );
		if ( $is_default ) {
			// Save the WooCommerce default address if we are saving that one.
			$address_book->set_default( $address_book_name );
		}
		wc_add_notice( __( 'Address changed successfully.', 'woocommerce' ) );
	}

	/**
	 * Hook: woocommerce_customer_save_address.
	 *
	 * Fires after a customer address has been saved.
	 *
	 * @since 3.6.0
	 * @param int          $user_id User ID being saved.
	 * @param string       $address_type Type of address; 'billing' or 'shipping'.
	 * @param array        $address The address fields. Since 9.8.0.
	 * @param \WC_Customer $customer The customer object being saved. Since 9.8.0.
	 */
	do_action( 'woocommerce_customer_save_address', $customer->get_id(), $address_type, $address, $customer );

	if ( 0 < wc_notice_count( 'error' ) ) {
		return;
	}

	wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
	exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\save_address_form_handler', 9 );
// Replace the WooCommerce Save Address with our own function, since it doesn't have the hooks needed to override it.
remove_action( 'template_redirect', array( 'WC_Form_Handler', 'save_address' ) );

/**
 * Replace the standard address with the one from the address book.
 *
 * @param array  $address The set of WooCommerce Address Fields.
 * @param string $load_address Address type to load, billing or shipping.
 * @return array
 */
function address_to_edit( $address, $load_address ) {
	if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$address_book_key = sanitize_text_field( wp_unslash( $_GET['address-book'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'new' === $address_book_key ) {
			// This is new address, blank out all values.
			foreach ( $address as $key => $settings ) {
				// Leave email and phone alone since they are most likely the same.
				if ( ! empty( $settings['value'] ) && 'billing_email' !== $key && 'billing_phone' !== $key ) {
					$address[ $key ]['value'] = null;
				}
			}
		} else {
			$customer = get_current_customer( 'address_to_edit' );
			if ( ! $customer ) {
				return $address;
			}
			$address_book    = get_address_book( $customer, $load_address );
			$current_address = $address_book->address( $address_book_key );
			foreach ( $address as $key => $settings ) {
				$current_key = str_replace( $load_address . '_', '', $key );
				if ( isset( $current_address[ $current_key ] ) ) {
					$address[ $key ]['value'] = $current_address[ $current_key ];
				} else {
					$address[ $key ]['value'] = null;
				}
			}
		}
	}

	return $address;
}
// Get the address book to load into the edit form.
add_filter( 'woocommerce_address_to_edit', __NAMESPACE__ . '\address_to_edit', 1000, 2 );

/**
 * Show an input to be able to get the shipping_calc setting into javascript.
 */
function woocommerce_before_checkout_shipping_form() {
	echo '<input type="hidden" id="woocommerce_enable_shipping_calc" value="' . esc_attr( get_option( 'woocommerce_enable_shipping_calc' ) ) . '" />';
}
add_action( 'woocommerce_before_checkout_shipping_form', __NAMESPACE__ . '\woocommerce_before_checkout_shipping_form' );

/**
 * Get the current user ID.
 *
 * @param string $context The context of where we get the current user id.
 * @return int
 */
function current_user_id( string $context = 'current_user_id' ) {
	/**
	 * Filter the current user id.
	 * Defaults to the current user ID.
	 *
	 * @since 3.1.0
	 * @param int    $user_id The current user id.
	 * @param string $context The context of where we get the current user id.
	 * @return int
	 */
	return apply_filters(
		'wc_address_book_current_user_id',
		get_current_user_id(),
		$context
	);
}

/**
 * Get the current customer.
 *
 * @param string $context The context of where we get the current customer.
 * @return ?\WC_Customer
 */
function get_current_customer( string $context = 'get_current_customer' ) {
	$user_id = current_user_id( $context );
	if ( ! $user_id ) {
		return null;
	}
	/**
	 * Filter the current customer.
	 *
	 * @since 3.1.0
	 * @param \WC_Customer $customer The current customer.
	 * @param string       $context The context of where we get the current customer.
	 * @return \WC_Customer
	 */
	return apply_filters(
		'wc_address_book_current_customer',
		new \WC_Customer( $user_id ),
		$context
	);
}
