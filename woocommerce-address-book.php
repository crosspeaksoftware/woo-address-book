<?php
/*
 * Plugin Name: WooCommerce Address Book
 * Plugin URI:
 * Description: Add multiple addresses to a user account to expatiate the checkout process.
 * Version: 1.0
 * Author: Hall Internet Marketing
 * Author URI: http://hallme.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: wc-address-book
 */

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Check if WooCommerce is active.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	class WC_Address_Book {

		/**
		 * Initializes the plugin by setting localization, filters, and administration functions.
		 *
		 * @since 1.0.0
		 */
		function __construct() {

			// Load plugin text domain
			add_action( 'init', array( $this, 'plugin_textdomain' ) );

			// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
			register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );

			// Enqueue Styles and Scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ), 20 );

			// Rename the shipping address label on the my accounts page.
			add_filter ( 'woocommerce_my_account_get_addresses' , array( $this, 'woocommerce_rename_shipping_address' ), 10, 1 );

			// Add a title field to the address edit screen for users.
			add_filter( 'woocommerce_address_to_edit', array( $this, 'add_address_label_to_edit' ), 10, 1 );

			// Add a title field to the address edit screen for users.
			add_action( 'woocommerce_customer_save_address', array( $this, 'update_address_book' ), 10, 2 );

			// Add custom Shipping Address fields.
			add_filter( 'woocommerce_checkout_fields', array( $this, 'shipping_address_select_field' ), 20, 1 );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'shipping_address_label_field' ), 10, 1 );

			// AJAX action to delete an address.
			add_action( 'wp_ajax_nopriv_wc_address_book_delete', array( $this, 'wc_address_book_delete' ) );
			add_action( 'wp_ajax_wc_address_book_delete', array( $this, 'wc_address_book_delete' ) );

			// AJAX action to set a primary address.
			add_action( 'wp_ajax_nopriv_wc_address_book_make_primary', array( $this, 'wc_address_book_make_primary' ) );
			add_action( 'wp_ajax_wc_address_book_make_primary', array( $this, 'wc_address_book_make_primary' ) );

			// AJAX action to refresh the address at checkout.
			add_action( 'wp_ajax_nopriv_wc_address_book_checkout_update', array( $this, 'wc_address_book_checkout_update' ) );
			add_action( 'wp_ajax_wc_address_book_checkout_update', array( $this, 'wc_address_book_checkout_update' ) );

			// Update the customer data with the information entered on checkout.
			add_filter ( 'woocommerce_checkout_update_customer_data', array( $this, 'woocommerce_checkout_update_customer_data' ), 10, 2 );

			// Add Address Book to Menu
			add_action( 'init', array( $this, 'add_endpoints' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_address_book_add_to_menu' ), 10 );
			add_action( 'woocommerce_account_address-book_endpoint', array( $this, 'wc_address_book_page' ), 10 );

		} // end constructor

		/**
		 * Fired when the plugin is activated.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function activate( $network_wide ) {
			flush_rewrite_rules();
		}

		/**
		 * Fired when the plugin is deactivated.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function deactivate( $network_wide ) {

			flush_rewrite_rules();

		}

		/**
		 * Fired when the plugin is uninstalled.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function uninstall( $network_wide ) {

			// TODO: remove all the additional addresses from the user_meta?
			flush_rewrite_rules();
		}

		/**
		 * Loads the plugin text domain for translation
		 *
		 * @since 1.0.0
		 */
		public function plugin_textdomain() {

			load_plugin_textdomain( 'wc-address-book', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

		}

		/**
		 * Enqueue scripts and styles
		 *
		 * @since	  1.0.0
		 */
		public function scripts_styles() {
			if ( ! is_admin() ) {
				wp_enqueue_script( 'jquery' );

				wp_enqueue_style( 'wc-address-book', plugins_url( '/assets/css/style.css', __FILE__ ) );
				wp_enqueue_script( 'wc-address-book', plugins_url( '/assets/js/scripts.js' , __FILE__ ), array('jquery'), '1.0', true );

				wp_localize_script( 'wc-address-book', 'wc_address_book', array(
					'ajax_url' => admin_url( 'admin-ajax.php' )
				));
			}
		}

		/**
		 * Rename the shipping address label on the my accounts page.
		 *
		 * @param array $labels - An array of address labels.
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_rename_shipping_address( $labels ) {

			if ( isset( $labels['shipping'] ) ) {

				$label = $this->get_label_by_name( 'shipping' );

				if ( ! isset( $label ) ) {
					$label = 'Shipping Address';
				}

				$labels['shipping'] = $label;
			}

			return $labels;
		}

		/**
		 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
		 *
		 * @since 1.0.0
		 */
		public function add_additional_address_button() {

			$user_id = get_current_user_id();
			$address_book = $this->get_address_book( $user_id );

			$name = $this->set_new_address_name( $address_book );

			?>

			<div class="add-new-address">
				<a href="<?php echo wc_get_endpoint_url( 'edit-address', $name ); ?>" class="add button"><?php _e( 'Add New Address', 'wc-address-book' ); ?></a>
			</div>

			<?php
		}

		public function set_new_address_name( $address_book ) {

            // Check the address book entries and add a new one.
            if ( isset( $address_book ) && ! empty( $address_book ) ) {

                // Get the last entry in the array and add 1
                $last_address = end( array_keys( $address_book ) );

                if( preg_match( '/\d+$/', $last_address, $matches ) ) {
                    $address_count = intval( $matches[0] );
                    $address_count = $address_count + 1;
                    $name = 'shipping' . $address_count;
                } else {
                    $name = 'shipping2';
                }

            } else { // Start the address book.

                $name = 'shipping';

            }

			return $name;

		}

		/**
		 * Register new endpoint to use inside My Account page.
		 *
		 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
		 */
		public function add_endpoints() {
		
			add_rewrite_endpoint( 'address-book', EP_ROOT | EP_PAGES );
		
		}

		/**
		 * Add new query var.
		 *
		 * @param array $vars
		 * @return array
		 */
		public function add_query_vars( $vars ) {
		
			$vars[] = 'address-book';
			return $vars;
		
		}

		/**
		 * Replace My Address with the Address Book to My Account Menu.
		 *
		 * @param Array $items - An array of menu items.
		 * @since 1.0.0
		 */
		function wc_address_book_add_to_menu( $items ) {

			$new_items = array();

			foreach ($items as $key => $value) {

				if ( $key === "edit-address" ) {
					$new_items["address-book"] = "Address Book";
				} else {
					$new_items[$key] = $value;
				}
			}

			return $new_items;
		}

		/**
		 * Adds Address Book Content.
		 *
		 * @since 1.0.0
		 */
		public function wc_address_book_page() {

			wc_get_template( 'myaccount/my-address.php' );

			wc_get_template( 'myaccount/my-address-book.php', array(), '', plugin_dir_path( __FILE__ ) . 'templates/' );

		}

		/**
		 * Returns an array of the users/customer additional address key value pairs.
		 *
		 * @param array $address -
		 * @since 1.0.0
		 */
		public function add_address_label_to_edit( $address ) {

			// Prevent address label from displaying on the billing address editor.
			if ( ! isset( $address['billing_first_name'] ) ) {

				$user_id = get_current_user_id();

				// Get the label value from the address book the unfun way.
				$address_book = $this->get_address_book( $user_id );

				reset($address);
				$first_key = key($address);
				$name = str_replace('_first_name', '', $first_key);
				$label = $this->get_label_by_name( $name );

				// Load the original array of inputs into a temp var.
				$address_temp = $address;

				// Empty the original out.
				$address = array();

				// Add out new input in the first position.
				$address['shipping_address_label'] = array(
					'type'		  => 'text',
					'label'       => __( 'Address Title', 'wc-address-book' ),
					'placeholder' => _x( 'Enter a title for this address', 'placeholder', 'wc-address-book' ),
					'required'    => false,
					'class'       => array( 'form-row-wide' ),
					'value'       => $label
				);

				// Add the original back in from the temp var.
				$address = $address + $address_temp;

			}

			// Return after all the shenanigans.
			return $address;
		}


		/**
		 * Update Address Book Values
		 *
		 * @param int $user_id - User's ID
		 * @param string $name - The name of the address being updated.
		 * @since 1.0.0
		 */
		public function update_address_book( $user_id, $name ) {

			// Get the address book and update the label
			$address_book = $this->get_address_book( $user_id );

			if ( ! empty( $_POST['billing_first_name'] ) ) {

				$address_label = 'Billing Address';

			} else if ( empty ( $_POST[ 'shipping_address_label' ] ) ) {

				$address_label = 'Shipping Address';

			} else {

				$address_label = esc_attr( $_POST[ 'shipping_address_label' ] );

			}

			$address_book[$name] = $address_label;

			$this->save_address_book( $user_id, $address_book );

		}

		/**
		 * Returns an array of the users/customer additional address key value pairs.
		 *
		 * @param int $user_id - User's ID
		 * @since 1.0.0
		 */
		public function get_address_book( $user_id ){

			$address_book = get_user_meta( $user_id, 'wc_address_book', true);

			return $address_book;

		}

		/**
		 * Gets the address label based on the address name.
		 *
		 * @param string $address_name - The address name.
		 * @since 1.0.0
		 */
		public function get_label_by_name( $address_name ) {

			$user = wp_get_current_user();
			$address_book = $this->get_address_book( $user->ID );

			if ( is_array( $address_book ) ) {

				foreach ( $address_book as $name => $label ) {
					if ( $address_name === $name ) {
						return $label;
					}
				}
			}
		}

		/**
		 * Returns an array of the users/customer additional address key value pairs.
		 *
		 * @param int $user_id - User's ID
		 * @since 1.0.0
		 */
		public function save_address_book( $user_id, $new_value ){

			// Make sure that is a new_value to save.
			if( !isset( $new_value ) ) {
				return;
			}

			// Update the value.
			$error_test = update_user_meta( $user_id, 'wc_address_book', $new_value );

			// If update_user_meta returns false, throw an error.
			if( !$error_test ) {
				// TODO: Add error notice.
			}
		}

		/**
		 * Adds the address book select to the checkout page.
		 *
		 * @param array $fields - An array of WooCommerce Shipping Address fields.
		 * @since 1.0.0
		 */
		public function shipping_address_select_field( $fields ) {

			$customer_id = get_current_user_id();
			$address_options = $this->get_address_book( $customer_id );

			//Check if additional addresses have been set.
			if ( null != get_user_meta( $customer_id, 'shipping_address_1' ) ) {

				$address_book['address_book'] = array(
					'type' => 'select',
					'class' => array( 'address_book' ),
					'label' => __( 'Address Book', 'woocommerce' ),
				);

				if ( ! empty( $address_options ) && false !== $address_options ) {

					foreach ( $address_options as $key => $value) {

						// Don't display the billing address.
						if ( $key == 'billing' ) {
							continue;
						}

						$address_book['address_book']['options'][$key] = $value;
					}
				}

				$address_book['address_book']['options']['add_new'] = 'Add New Address';
				$fields['shipping'] = $address_book + $fields['shipping'];
			}

			return $fields;
		}

		/**
		 * Adds the address label select to the checkout page.
		 *
		 * @param array $fields - An array of WooCommerce Shipping Address fields.
		 * @since 1.0.0
		 */
		public function shipping_address_label_field( $fields ) {

			$customer_id = get_current_user_id();

			//Check if additional addresses have been set.
			$address_label['shipping_address_label'] = array(
				'type' => 'text',
				'class' => array( 'shipping_address_label' ),
				'label' => __( 'Address Title', 'woocommerce' ),
				'placeholder' => _x( 'Enter a title for this address', 'placeholder', 'wc-address-book' ),
			);

			$fields['shipping'] = $address_label + $fields['shipping'];

			return $fields;
		}


		/**
		 * Used for deleting addresses from the my-account page.
		 *
		 * @since 1.0.0
		 */
		function wc_address_book_delete() {

			$address_name = $_POST['name'];
			$customer_id = get_current_user_id();
			$address_book = $this->get_address_book( $customer_id );

			foreach ( $address_book as $name => $label ) {

				if ( $address_name === $name ) {

					// Remove address from address book.
					unset( $address_book[$name] );

					// Update the value.
					$error_test = update_user_meta( $customer_id, 'wc_address_book', $address_book );

					// If update_user_meta returns false, throw an error.
					if( !$error_test ) {
						// TODO: Add error notice.
					}

					// Remove specific address values.
					delete_user_meta( $customer_id, $name . '_first_name' );
					delete_user_meta( $customer_id, $name . '_last_name' );
					delete_user_meta( $customer_id, $name . '_company' );
					delete_user_meta( $customer_id, $name . '_address_1' );
					delete_user_meta( $customer_id, $name . '_address_2' );
					delete_user_meta( $customer_id, $name . '_city' );
					delete_user_meta( $customer_id, $name . '_state' );
					delete_user_meta( $customer_id, $name . '_postcode' );
					delete_user_meta( $customer_id, $name . '_country' );
					break;
				}
			}

			die();
		}

		/**
		 * Used for setting the primary shipping addresses from the my-account page.
		 *
		 * @since 1.0.0
		 */
		function wc_address_book_make_primary() {

			$customer_id = get_current_user_id();
			$address_book = $this->get_address_book( $customer_id );

			$alt_address_name = $_POST['name'];
			$primary_address_name = 'shipping';

			$alt_label = $this->get_label_by_name( $alt_address_name );
			$primary_label = $this->get_label_by_name( $primary_address_name );

			$address_book[$alt_address_name] = $primary_label;
			$address_book[$primary_address_name] = $alt_label;

			$this->save_address_book( $customer_id, $address_book );

			$address_fields = array(
				'_first_name',
				'_last_name',
				'_company',
				'_address_1',
				'_address_2',
				'_city',
				'_state',
				'_postcode',
				'_country'
			);

			// Get Primary and Alt Shipping values
			foreach ( $address_fields as $field ) {

				$primary_shipping['shipping' . $field] = get_user_meta( $customer_id, 'shipping' . $field, true );
				$alt_shipping[$alt_address_name . $field] = get_user_meta( $customer_id, $alt_address_name . $field, true );

				update_user_meta( $customer_id, 'shipping' . $field , $alt_shipping[$alt_address_name . $field] );

				if ( $_POST['name'] != 'billing' ) {
					update_user_meta( $customer_id, $alt_address_name . $field , $primary_shipping['shipping' . $field] );
				}
			}

			die();
		}

		/**
		 * Used for updating addresses dynamically on the checkout page.
		 *
		 * @since 1.0.0
		 */
		function wc_address_book_checkout_update() {

			global $woocommerce;

			$address_name = $_POST['name'];
			$label = $this->get_label_by_name( $address_name );
			$customer_id = get_current_user_id();
			$shipping_countries = $woocommerce->countries->get_shipping_countries();
			$address_fields = array(
				'_first_name',
				'_last_name',
				'_company',
				'_address_1',
				'_address_2',
				'_city',
				'_state',
				'_postcode',
				'_country'
			);

			// Get address field values.
			if ( 'add_new' !== $address_name ) {

				$response['shipping_address_label'] = $label;

				foreach ( $address_fields as $field ) {

					$response['shipping' . $field] = get_user_meta( $customer_id, $address_name . $field, true );
				}

			} else {

				foreach ( $address_fields as $field ) {

					// If only one country is available for shipping, include it in the blank form.
					if ( '_country' === $field && 1 === count( $shipping_countries )  ) {
						$response['shipping' . $field] = key( $shipping_countries );
					} else {
						$response['shipping' . $field] = '';
					}
				}
			}
			echo json_encode( $response );

			die();
		}

		/**
		 * Update the customer data with the information entered on checkout.
		 *
		 * @param boolean $update_customer_data - Toggles whether Woo should update customer data on checkout. This plugin overrides that function entirely.
		 *
		 * @param Object $checkout_object - An object of the checkout fields and values.
		 *
		 * @since 1.0.0
		 */
		public function woocommerce_checkout_update_customer_data( $update_customer_data, $checkout_object ) {

			$name = $checkout_object->posted['address_book'];
			$user = wp_get_current_user();
			$address_book = $this->get_address_book( $user->ID );
			$update_customer_data = false;
			$ignore_shipping_address = false;

			if ( $checkout_object->posted['ship_to_different_address'] != true ) {
				$ignore_shipping_address = true;
			}

			// Name new address and update address book.
			if  ( ( 'add_new' === $name || ! isset( $name ) ) && false === $ignore_shipping_address ) {
				$name = $this->set_new_address_name( $address_book );
			}

			if ( false === $ignore_shipping_address ) {
				$this->update_address_book( $user->ID, $name );
			}

			// Billing address
			$billing_address = array();
			if ( $checkout_object->checkout_fields['billing'] ) {
				foreach ( array_keys( $checkout_object->checkout_fields['billing'] ) as $field ) {
					$field_name = str_replace( 'billing_', '', $field );
					$billing_address[ $field_name ] = $checkout_object->get_posted_address_data( $field_name );
				}
			}

			// Shipping address.
			$shipping_address = array();
			if ( $checkout_object->checkout_fields['shipping'] ) {
				foreach ( array_keys( $checkout_object->checkout_fields['shipping'] ) as $field ) {
					$field_name = str_replace( 'shipping_', '', $field );

					// Prevent address book and label fields from being written to the DB.
					if ( 'address_book' === $field_name || 'address_label' === $field_name ) {
						continue;
					}

					$shipping_address[ $field_name ] = $checkout_object->get_posted_address_data( $field_name, 'shipping' );
				}
			}

			foreach ( $billing_address as $key => $value ) {
				update_user_meta( $user->ID, 'billing_' . $key, $value );
			}
			if ( WC()->cart->needs_shipping() && false === $ignore_shipping_address ) {
				foreach ( $shipping_address as $key => $value ) {
					update_user_meta( $user->ID, $name . '_' . $key, $value );
				}
			}

			return $update_customer_data;
		}

	} // end class

	// Init Class
	$wc_address_book = new WC_Address_Book();

}