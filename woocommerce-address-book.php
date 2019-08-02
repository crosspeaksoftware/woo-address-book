<?php
/**
 * Plugin Name: WooCommerce Address Book
 * Description: Gives your customers the option to store multiple shipping addresses and retrieve them on checkout..
 * Version: 1.5.6
 * Author: Hall Internet Marketing
 * Author URI: https://www.hallme.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-address-book
 * WC tested up to: 3.6.2
 *
 * @package WooCommerce Address Book
 */

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$woo_path = 'woocommerce/woocommerce.php';

if ( ! is_plugin_active( $woo_path ) && ! is_plugin_active_for_network( $woo_path ) ) {

	deactivate_plugins( plugin_basename( __FILE__ ) );

	/**
	 * Deactivate the plugin if WooCommerce is not active.
	 *
	 * @since    1.0.0
	 */
	function wc_address_book_woocommerce_notice_error() {

		$class   = 'notice notice-error';
		$message = __( 'WooCommerce Address Book requires WooCommerce and has been deactivated.', 'woo-address-book' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_attr( $message ) );
	}
	add_action( 'admin_notices', 'wc_address_book_woocommerce_notice_error' );
	add_action( 'network_admin_notices', 'wc_address_book_woocommerce_notice_error' );

} else {

	/**
	 * WooCommerce Address Book.
	 *
	 * @class    WC_Address_Book
	 * @version  1.3.3
	 * @package  WooCommerce Address Book
	 * @category Class
	 * @author   Hall Internet Marketing
	 */
	class WC_Address_Book {

		/**
		 * Initializes the plugin by setting localization, filters, and administration functions.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Version Number.
			$this->version = '1.5.6';

			// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( 'WC_Address_Book', 'deactivate' ) );
			register_uninstall_hook( __FILE__, array( 'WC_Address_Book', 'uninstall' ) );

			// Enqueue Styles and Scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ), 20 );

			// Save an address to the address book.
			add_action( 'woocommerce_customer_save_address', array( $this, 'update_address_names' ), 10, 2 );
			add_action( 'woocommerce_customer_save_address', array( $this, 'redirect_on_save' ), 9999, 2 );

			// Add custom Shipping Address fields.
			add_filter( 'woocommerce_checkout_fields', array( $this, 'shipping_address_select_field' ), 9999, 1 );

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
			add_filter( 'woocommerce_checkout_update_customer_data', array( $this, 'woocommerce_checkout_update_customer_data' ), 10, 2 );

			// Add Address Book to Menu.
			add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_address_book_add_to_menu' ), 10 );
			add_action( 'woocommerce_account_edit-address_endpoint', array( $this, 'wc_address_book_page' ), 20 );

			// Shipping Address fields.
			add_filter( 'woocommerce_form_field_country', array( $this, 'shipping_address_country_select' ), 20, 4 );

			// Standardize the address edit fields to match Woo's IDs.
			add_filter( 'woocommerce_form_field_args', array( $this, 'standardize_field_ids' ), 20, 3 );

			add_filter( 'woocommerce_shipping_fields', array( $this, 'replace_address_key' ), 1001, 2 );

			// Hook in before address save.
			add_action( 'template_redirect', array( $this, 'before_save_address' ), 9 );

			// Adds support for address nicknames
			add_filter( 'woocommerce_shipping_fields', array( $this, 'add_address_nickname_field' ), 10, 1 );
			add_action( 'wp', array( $this, 'validate_address_nickname_filter' ) );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'address_nickname_field_replacement' ), 10, 2 );
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_nickname_localization_format' ), -10 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'formatted_address_nickname' ), 10, 3 );
			add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_nickname_field_from_checkout' ) );

		} // end constructor

		/**
		 * Version
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version;

		/**
		 * Fired when the plugin is activated.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function activate( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			flush_rewrite_rules();
		}

		/**
		 * Fired when the plugin is deactivated.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function deactivate( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			flush_rewrite_rules();

		}

		/**
		 * Fired when the plugin is uninstalled.
		 *
		 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
		 * @since 1.0.0
		 */
		public function uninstall( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			flush_rewrite_rules();
		}

		/**
		 * Enqueue scripts and styles
		 *
		 * @since 1.0.0
		 */
		public function scripts_styles() {

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_style( 'woo-address-book', plugins_url( '/assets/css/style.css', __FILE__ ), array(), $this->version );
			wp_register_script( 'woo-address-book', plugins_url( "/assets/js/scripts$min.js", __FILE__ ), array( 'jquery' ), $this->version, true );

			wp_localize_script(
				'woo-address-book',
				'woo_address_book',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);

			if ( is_account_page() ) {
				wp_enqueue_style( 'woo-address-book' );
			}

			if ( is_account_page() || is_checkout() ) {
				wp_enqueue_script( 'woo-address-book' );
			}
		}

		/**
		 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
		 *
		 * @since 1.0.0
		 */
		public function add_additional_address_button() {

			$user_id       = get_current_user_id();
			$address_names = $this->get_address_names( $user_id );

			$name = $this->set_new_address_name( $address_names );

			?>

			<div class="add-new-address">
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'shipping', get_permalink() . '?address-book=' . $name ) ); ?>" class="add button"><?php echo esc_html_e( 'Add New Shipping Address', 'woo-address-book' ); ?></a>
			</div>

			<?php
		}

		/**
		 * Returns the next available shipping address name.
		 *
		 * @param string $address_names - An array of saved address names.
		 * @since 1.0.0
		 */
		public function set_new_address_name( $address_names ) {

			// Check the address book entries and add a new one.
			if ( ! empty( $address_names ) && is_array( $address_names ) ) {
				// Find the first address name that doesn't exist.
				$counter = 2;
				do {
					$name = 'shipping' . $counter;
					$counter++;
				} while ( in_array( $name, $address_names, true ) );
			} else { // Start the address book.

				$name = 'shipping';

			}

			return $name;

		}

		/**
		 * Replace My Address with the Address Book to My Account Menu.
		 *
		 * @param array $items - An array of menu items.
		 * @return array
		 * @since 1.0.0
		 */
		public function wc_address_book_add_to_menu( $items ) {

			foreach ( $items as $key => $value ) {
				if ( 'edit-address' === $key ) {
					$items[ $key ] = __( 'Address Book', 'woo-address-book' );
				}
			}

			return $items;
		}

		/**
		 * Adds Address Book Content.
		 *
		 * @param string $type - The type of address.
		 * @since 1.0.0
		 */
		public function wc_address_book_page( $type ) {

			wc_get_template( 'myaccount/my-address-book.php', array( 'type' => $type ), '', plugin_dir_path( __FILE__ ) . 'templates/' );

		}

		/**
		 * Modify the shipping address field to allow for available countries to displayed correctly. Overrides most of woocommerce_form_field().
		 * TODO: Figure out how to override the countries here without copying the entire function.
		 *
		 * @since 1.0.0
		 *
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param mixed  $args Arguments.
		 * @param string $value (default: null).
		 * @return string
		 */
		public function shipping_address_country_select( $field, $key, $args, $value ) {

			if ( $args['required'] ) {
				$args['class'][] = 'validate-required';
				$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
			} else {
				$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
			}

			if ( is_string( $args['label_class'] ) ) {
				$args['label_class'] = array( $args['label_class'] );
			}

			if ( is_null( $value ) ) {
				$value = $args['default'];
			}

			// Custom attribute handling.
			$custom_attributes         = array();
			$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

			if ( $args['maxlength'] ) {
				$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
			}

			if ( ! empty( $args['autocomplete'] ) ) {
				$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
			}

			if ( true === $args['autofocus'] ) {
				$args['custom_attributes']['autofocus'] = 'autofocus';
			}

			if ( $args['description'] ) {
				$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
			}

			if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
				foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			if ( ! empty( $args['validate'] ) ) {
				foreach ( $args['validate'] as $validate ) {
					$args['class'][] = 'validate-' . $validate;
				}
			}

			$field           = '';
			$label_id        = $args['id'];
			$sort            = $args['priority'] ? $args['priority'] : '';
			$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

			/**
			* HALL EDIT: The primary purpose for this override is to replace the default 'shipping_country' with 'billing_country'.
			*/

			$countries = 'billing_country' === $key ? WC()->countries->get_allowed_countries() : WC()->countries->get_shipping_countries();

			if ( 1 === count( $countries ) ) {

				$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

				$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';

			} else {

				$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . '><option value="">' . esc_html__( 'Select a country&hellip;', 'woocommerce' ) . '</option>';

				foreach ( $countries as $ckey => $cvalue ) {
					$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
				}

				$field .= '</select>';

				$field .= '<noscript><button type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country', 'woocommerce' ) . '">' . esc_html__( 'Update country', 'woocommerce' ) . '</button></noscript>';

			}

			if ( ! empty( $field ) ) {
				$field_html = '';

				if ( $args['label'] && 'checkbox' !== $args['type'] ) {
					$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
				}

				$field_html .= '<span class="woocommerce-input-wrapper">' . $field;

				if ( $args['description'] ) {
					$field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
				}

				$field_html .= '</span>';

				$container_class = esc_attr( implode( ' ', $args['class'] ) );
				$container_id    = esc_attr( $args['id'] ) . '_field';
				$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
			}

			return $field;
		}

		/**
		 * Update Address Book Values
		 *
		 * @since 1.0.0
		 *
		 * @param int    $user_id - User's ID.
		 * @param string $name - The name of the address being updated.
		 */
		public function update_address_names( $user_id, $name ) {

			if ( isset( $_GET['address-book'] ) ) {
				$name = trim( $_GET['address-book'], '/' );
			}

			// Only save shipping addresses.
			if ( 'billing' === $name ) {
				return;
			}

			// Get the address book and update the label.
			$address_names = $this->get_address_names( $user_id );

			// Build new array if one does not exist.
			if ( ! is_array( $address_names ) || empty( $address_names ) ) {

				$address_names = array();
			}

			// Add shipping name if not already in array.
			if ( ! in_array( $name, $address_names, true ) ) {

				array_push( $address_names, $name );
				$this->save_address_names( $user_id, $address_names );
			}

		}

		/**
		 * Redirect to the Edit Address page on save. Overrides the default redirect to /my-account/
		 *
		 * @since 1.0.0
		 *
		 * @param int    $user_id - User's ID.
		 * @param string $name - The name of the address being updated.
		 */
		public function redirect_on_save( $user_id, $name ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {

				wp_safe_redirect( wc_get_account_endpoint_url( 'edit-address' ) );
				exit;
			}
		}

		/**
		 * Returns an array of the customer's address names.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id - User's ID.
		 * @return array
		 */
		public function get_address_names( $user_id = null ) {

			$address_names = get_user_meta( $user_id, 'wc_address_book', true );

			if ( empty( $address_names ) ) {
				$shipping_address = get_user_meta( $user_id, 'shipping_address_1', true );
				// Return just a default shipping address if no other addresses are saved.
				if ( ! empty( $shipping_address ) ) {
					return array( 'shipping' );
				}
				// If we don't have a shipping address, just return an empty array.
				return array();
			}
			return $address_names;
		}

		/**
		 * Returns an array of the customer's addresses with field values.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id - User's ID.
		 * @return array
		 */
		public function get_address_book( $user_id = null ) {

			$countries = new WC_Countries();

			if ( ! isset( $country ) ) {
				$country = $countries->get_base_country();
			}

			if ( ! isset( $user_id ) ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}

			$address_names = $this->get_address_names( $user_id );

			$address_fields = WC()->countries->get_address_fields( $country, 'shipping_' );

			// Get the set shipping fields, including any custom values.
			$address_keys = array_keys( $address_fields );

			$address_book = array();

			if ( ! empty( $address_names ) ) {

				foreach ( $address_names as $name ) {

					// Do not include the billing address.
					if ( 'billing' === $name ) {
						continue;
					}

					unset( $address );

					foreach ( $address_keys as $field ) {

						// Remove the default name so the custom ones can be added.
						$field = str_replace( 'shipping', '', $field );

						$address[ $name . $field ] = get_user_meta( $user_id, $name . $field, true );

					}

					$address_book[ $name ] = $address;

				}
			}

			return apply_filters( 'wc_address_book_addresses', $address_book );

		}

		/**
		 * Returns an array of the users/customer additional address key value pairs.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $user_id User's ID.
		 * @param array $new_value Address book names.
		 */
		public function save_address_names( $user_id, $new_value ) {

			// Make sure that is a new_value to save.
			if ( ! isset( $new_value ) ) {
				return;
			}

			// Update the value.
			$error_test = update_user_meta( $user_id, 'wc_address_book', $new_value );

			// If update_user_meta returns false, throw an error.
			if ( ! $error_test ) {
				// TODO: Add error notice.
			}
		}

		/**
		 * Adds the address book select to the checkout page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields An array of WooCommerce Shipping Address fields.
		 * @return array
		 */
		public function shipping_address_select_field( $fields ) {

			if ( is_user_logged_in() ) {

				$address_book = $this->get_address_book();

				$address_selector['address_book'] = array(
					'type'     => 'select',
					'class'    => array( 'form-row-wide', 'address_book' ),
					'label'    => __( 'Address Book', 'woo-address-book' ),
					'order'    => -1,
					'priority' => -1,
				);

				if ( ! empty( $address_book ) && false !== $address_book ) {

					foreach ( $address_book as $name => $address ) {

						if ( ! empty( $address[ $name . '_address_1' ] ) ) {
							$address_selector['address_book']['options'][ $name ] = $this->address_select_label( $address, $name );
						}
					}

					$address_selector['address_book']['options']['add_new'] = __( 'Add New Address', 'woo-address-book' );

					$fields['shipping'] = $address_selector + $fields['shipping'];

				}
			}

			return $fields;
		}

		/**
		 * Adds the address book select to the checkout page.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $address An array of WooCommerce Shipping Address data.
		 * @param string $name Name of the address field to use.
		 * @return string
		 */
		public function address_select_label( $address, $name ) {

			$label = '';

			$address_nickname = get_user_meta( get_current_user_id(), $name.'_address_nickname', true );
			if ( $address_nickname ){
				$label .= $address_nickname . ': ';
			}

			if ( ! empty( $address[ $name . '_first_name' ] ) ) {
				$label .= $address[ $name . '_first_name' ];
			}
			if ( ! empty( $address[ $name . '_last_name' ] ) ) {
				if ( ! empty( $label ) ) {
					$label .= ' ';
				}
				$label .= $address[ $name . '_last_name' ];
			}
			if ( ! empty( $address[ $name . '_address_1' ] ) ) {
				if ( ! empty( $label ) ) {
					$label .= ', ';
				}
				$label .= $address[ $name . '_address_1' ];
			}
			if ( ! empty( $address[ $name . '_city' ] ) ) {
				if ( ! empty( $label ) ) {
					$label .= ', ';
				}
				$label .= $address[ $name . '_city' ];
			}
			if ( ! empty( $address[ $name . '_state' ] ) ) {
				if ( ! empty( $label ) ) {
					$label .= ', ';
				}
				$label .= $address[ $name . '_state' ];
			}

			return apply_filters( 'wc_address_book_address_select_label', $label, $address, $name );
		}

		/**
		 * Used for deleting addresses from the my-account page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $address_name The name of a specific address in the address book.
		 */
		public function wc_address_book_delete( $address_name ) {

			$address_name  = $_POST['name'];
			$customer_id   = get_current_user_id();
			$address_book  = $this->get_address_book( $customer_id );
			$address_names = $this->get_address_names( $customer_id );

			foreach ( $address_book as $name => $address ) {

				if ( $address_name === $name ) {

					// Remove address from address book.
					$key = array_search( $name, $address_names, true );
					if ( ( $key ) !== false ) {
						unset( $address_names[ $key ] );
					}

					$this->save_address_names( $customer_id, $address_names );

					// Remove specific address values.
					foreach ( $address as $field => $value ) {

						delete_user_meta( $customer_id, $field );
					}

					break;
				}
			}

			if ( is_ajax() ) {
				die();
			}
		}

		/**
		 * Used for setting the primary shipping addresses from the my-account page.
		 *
		 * @since 1.0.0
		 */
		public function wc_address_book_make_primary() {

			$customer_id  = get_current_user_id();
			$address_book = $this->get_address_book( $customer_id );

			$primary_address_name = 'shipping';
			$alt_address_name     = $_POST['name'];

			// Loop through and swap values between shipping names.
			foreach ( $address_book[ $primary_address_name ] as $field => $value ) {

				$alt_field = preg_replace( '/^[^_]*_\s*/', $alt_address_name . '_', $field );
				$resp      = update_user_meta( $customer_id, $field, $address_book[ $alt_address_name ][ $alt_field ] );
			}

			foreach ( $address_book[ $alt_address_name ] as $field => $value ) {

				$primary_field = preg_replace( '/^[^_]*_\s*/', $primary_address_name . '_', $field );
				$resp          = update_user_meta( $customer_id, $field, $address_book[ $primary_address_name ][ $primary_field ] );
			}

			die();
		}

		/**
		 * Used for updating addresses dynamically on the checkout page.
		 *
		 * @since 1.0.0
		 */
		public function wc_address_book_checkout_update() {

			global $woocommerce;

			$name         = $_POST['name'];
			$address_book = $this->get_address_book();

			$customer_id        = get_current_user_id();
			$shipping_countries = $woocommerce->countries->get_shipping_countries();

			$response = array();

			// Get address field values.
			if ( 'add_new' !== $name ) {

				foreach ( $address_book[ $name ] as $field => $value ) {

					$field = preg_replace( '/^[^_]*_\s*/', 'shipping_', $field );

					$response[ $field ] = $value;
				}
			} else {

				// If only one country is available for shipping, include it in the blank form.
				if ( 1 === count( $shipping_countries ) ) {
					$response['shipping_country'] = key( $shipping_countries );
				}
			}

			echo wp_json_encode( $response );

			die();
		}

		/**
		 * Update the customer data with the information entered on checkout.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $update_customer_data - Toggles whether Woo should update customer data on checkout. This plugin overrides that function entirely.
		 * @param object  $checkout_object - An object of the checkout fields and values.
		 * @return boolean
		 */
		public function woocommerce_checkout_update_customer_data( $update_customer_data, $checkout_object ) {

			$name                    = isset( $_POST['address_book'] ) ? $_POST['address_book'] : false;
			$user                    = wp_get_current_user();
			$address_book            = $this->get_address_book( $user->ID );
			$update_customer_data    = false;
			$ignore_shipping_address = true;

			if ( isset( $_POST['ship_to_different_address'] ) && $_POST['ship_to_different_address'] ) {
				$ignore_shipping_address = false;
			}

			// Name new address and update address book.
			if ( ( 'add_new' === $name || false === $name ) && false === $ignore_shipping_address ) {

				$address_names = $this->get_address_names( $user->ID );

				$name = $this->set_new_address_name( $address_names );
			}

			if ( false === $ignore_shipping_address ) {
				$this->update_address_names( $user->ID, $name );
			}

			// Billing address.
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

		/**
		 * Standardize the address edit fields to match Woo's IDs.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args - The set of arguments being passed to the field.
		 * @param string $key - The name of the address being edited.
		 * @param string $value - The value a field will be prepopulated with.
		 * @return array
		 */
		public function standardize_field_ids( $args, $key, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			if ( 'address_book' !== $key ) {
				$args['id'] = preg_replace( '/^shipping[^_]+/', 'shipping', $args['id'] );
			}

			return $args;
		}

		/**
		 * Actions before save_address is called.
		 *
		 * Update the country field to get around the validation check in save_address
		 * in woocommerce/includes/class-wc-form-handler.php
		 *
		 * @since 1.5.0
		 */
		public function before_save_address() {

			if ( empty( $_REQUEST['woocommerce-edit-address-nonce'] ) || empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				return;
			}

			if ( isset( $_GET['address-book'] ) ) {
				$name = trim( $_GET['address-book'], '/' );
				if ( isset( $_POST[ $name . '_country' ] ) ) {
					// Copy to shipping_country to bypass the check in save address.
					$_POST['shipping_country'] = $_POST[ $name . '_country' ];
				}
			}

		}

		/**
		 * Replace the standard 'Shipping' address key with address book key.
		 *
		 * @since 1.1.0
		 *
		 * @param array $address_fields - The set of WooCommerce Address Fields.
		 * @return array
		 */
		public function replace_address_key( $address_fields ) {

			if ( isset( $_GET['address-book'] ) ) {

				$user_id       = get_current_user_id();
				$address_names = $this->get_address_names( $user_id );

				// If a version of the address name exists with a slash, use it. Otherwise, trim the slash.
				// Previous versions of this plugin was including the slash in the address name.
				// While not causing problems, it should not have happened in the first place.
				// This enables backward compatibility.
				if ( in_array( $_GET['address-book'], $address_names, true ) ) {
					$name = $_GET['address-book'];
				} else {
					$name = trim( $_GET['address-book'], '/' );
				}

				foreach ( $address_fields as $key => $value ) {

					$new_key = str_replace( 'shipping', esc_attr( $name ), $key );

					$address_fields[ $new_key ] = $address_fields[ $key ];
					unset( $address_fields[ $key ] );
				}
			}

			return $address_fields;
		}

		public function add_address_nickname_field( $address_fields ) {

			if ( !isset( $address_fields['shipping_address_nickname'] ) ){

				$address_fields['shipping_address_nickname'] = array(
					'label' 	 		 => __( 'Address nickname','woo-address-book' ),
					'required' 		 => false,
					'class'		 		 => array('form-row-wide'),
					'autocomplete' => 'given-name',
					'priority' 		 => -1,
					'value'		 		 => '',
					'description'	 => __( 'Will help you identify your addresses easily. Suggested nicknames: Home, Work...', 'woo-address-book' ),
					'validate'		 => array('address-nickname')
				);

			}

			return $address_fields;

		}

		public function validate_address_nickname_filter() {

			if ( is_wc_endpoint_url( 'edit-address' ) ){

				$address_name	= 'shipping';//default

				if ( !empty( $_GET['address-book'] ) ){
					$address_name = sanitize_text_field( $_GET['address-book'] );
				}

				if ( preg_match( '/shipping\d*$/', $address_name ) ){
					add_filter( 'woocommerce_process_myaccount_field_'.$address_name.'_address_nickname', array( $this, 'validate_address_nickname' ), 10, 1 );
				}

			}

		}

		public function validate_address_nickname( $new_nickname ) {

			$address_names = get_user_meta( get_current_user_id(), 'wc_address_book', true );

			if ( is_array( $address_names ) ){

				foreach ( $address_names as $address_name ){

					$address_nickname = get_user_meta( get_current_user_id(), $address_name.'_address_nickname', true );

					if( !empty( $new_nickname ) && sanitize_title( $address_nickname ) == sanitize_title( $new_nickname ) ){
						//address nickname should be unique
						wc_add_notice( __( 'Address nickname should be unique, another address is using the nickname.', 'woo-address-book' ), 'error' );
						$new_nickname = false;
						break;
					}

				}

			}

			return mb_strtoupper( $new_nickname );

		}

		public function address_nickname_field_replacement( $address, $args ) {
			$address['{address_nickname}'] = '';

			if ( !empty( $args['address_nickname'] ) ) {
				$address['{address_nickname}'] = $args['address_nickname'];
			}

			return $address;
		}

		public function address_nickname_localization_format( $formats ) {

			foreach ( $formats as $iso_code => $format ) {
				$formats[$iso_code] = "{address_nickname}\n" . $formats[$iso_code];
			}

			return $formats;
		}

		public function formatted_address_nickname( $fields, $customer_id, $type ) {

			if ( substr( $type, 0, 8 ) === "shipping" ) {
				$fields['address_nickname'] = get_user_meta( $customer_id, $type . '_address_nickname', true );
			}

			return $fields;
		}

		public function remove_nickname_field_from_checkout( $fields ) {

			unset( $fields['shipping']['shipping_address_nickname'] );

			return $fields;

		}

	} // end class

	// Init Class.
	$wc_address_book = new WC_Address_Book();

}
