<?php
/**
 * WooCommerce Address Book.
 *
 * @class    WC_Address_Book
 * @version  2.6.4
 * @package  WooCommerce Address Book
 */
class WC_Address_Book {

	/**
	 * Instance of this class.
	 *
	 * @since    1.6.0
	 *
	 * @var     object
	 */
	protected static $instance = null;

	/**
	 * Version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Version Number.
		$this->version = '2.6.4';

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'WC_Address_Book', 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( 'WC_Address_Book', 'uninstall' ) );

		// Enqueue Styles and Scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );

		// Load Plugin Textdomain for localization.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Save an address to the address book.
		add_action( 'woocommerce_customer_save_address', array( $this, 'update_address_names' ), 10, 2 );
		add_action( 'woocommerce_customer_save_address', array( $this, 'redirect_on_save' ), 9999, 2 );

		// Add custom address fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_address_select_field' ), 9999, 1 );

		// AJAX action to delete an address.
		add_action( 'wc_ajax_wc_address_book_delete', array( $this, 'wc_address_book_delete' ) );

		// AJAX action to set a primary address.
		add_action( 'wc_ajax_wc_address_book_make_primary', array( $this, 'wc_address_book_make_primary' ) );

		// AJAX action to refresh the address at checkout.
		add_action( 'wc_ajax_wc_address_book_checkout_update', array( $this, 'wc_address_book_checkout_update' ) );

		// Update the customer data with the information entered on checkout.
		add_filter( 'woocommerce_checkout_update_customer_data', array( $this, 'woocommerce_checkout_update_customer_data' ), 10, 2 );

		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'woocommerce_before_checkout_shipping_form' ) );

		// Add Address Book to Menu.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_address_book_add_to_menu' ), 10 );
		add_action( 'woocommerce_account_edit-address_endpoint', array( $this, 'wc_address_book_page' ), 20 );

		// Address select fields.
		add_filter( 'woocommerce_form_field_country', array( $this, 'address_country_select' ), 20, 4 );

		// Standardize the address edit fields to match Woo's IDs.
		add_filter( 'woocommerce_form_field_args', array( $this, 'standardize_field_ids' ), 20, 3 );

		add_filter( 'woocommerce_billing_fields', array( $this, 'replace_address_key' ), 10001, 2 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'replace_address_key' ), 10001, 2 );

		// Hook in before address save.
		add_action( 'template_redirect', array( $this, 'before_save_address' ), 9 );

		// WooCommerce Subscriptions support.
		add_filter( 'woocommerce_billing_fields', array( $this, 'remove_address_subscription_update_box' ), 10, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'remove_address_subscription_update_box' ), 10, 1 );

		// Adds support for address nicknames.
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_billing_address_nickname_field' ), 10, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_address_nickname_field' ), 10, 1 );
		add_action( 'wp', array( $this, 'validate_address_nickname_filter' ) );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'address_nickname_field_replacement' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_nickname_localization_format' ), -10 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'get_address_nickname' ), 10, 3 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_nickname_field_from_checkout' ) );
	} // end constructor

	/**
	 * Load plugin option.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name The option name.
	 * @param string $default The default value for the setting.
	 * @return boolean
	 */
	public function get_wcab_option( $name, $default = 'yes' ) {
		$option = get_option( 'woo_address_book_' . $name, $default );
		if ( 'yes' === $option ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.6.0
	 *
	 * @return   object  A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load plugin textdomain for localization.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woo-address-book', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages/' );
	}

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
		wp_register_style( 'woo-address-book', plugins_url( "/assets/css/style$min.css", dirname( __FILE__ ) ), array(), $this->version );
		wp_register_script( 'woo-address-book', plugins_url( "/assets/js/scripts$min.js", dirname( __FILE__ ) ), array( 'jquery', 'jquery-blockui' ), $this->version, true );

		wp_localize_script(
			'woo-address-book',
			'woo_address_book',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'wc_ajax_url'         => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '?wc-ajax=%%endpoint%%',
				'delete_security'     => wp_create_nonce( 'woo-address-book-delete' ),
				'primary_security'    => wp_create_nonce( 'woo-address-book-primary' ),
				'checkout_security'   => wp_create_nonce( 'woo-address-book-checkout' ),
				'delete_confirmation' => __( 'Are you sure you want to delete this address?', 'woo-address-book' ),
				'allow_readonly'      => $this->get_wcab_option( 'block_readonly' ) === true ? 'no' : 'yes',
			)
		);

		if ( is_account_page() || is_checkout() ) {
			wp_enqueue_style( 'woo-address-book' );
		}

		if ( is_account_page() || is_checkout() ) {
			wp_enqueue_script( 'woo-address-book' );
		}
	}

	/**
	 * Get address type from name
	 *
	 * @param string $name Address name.
	 *
	 * @since 1.8.0
	 */
	public function get_address_type( $name ) {
		$type = preg_replace( '/\d/', '', $name );
		return $type;
	}

	/**
	 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @since 1.0.0
	 */
	public function add_additional_address_button( $type ) {
		$user_id       = get_current_user_id();
		$address_names = $this->get_address_names( $user_id, $type );
		$name          = $this->set_new_address_name( $address_names, $type );
		$under_limit   = $this->limit_saved_addresses( $type );

		$add_button_classes = 'add button wc-address-book-add-' . $type . '-button';

		?>

		<?php if ( apply_filters( 'wc_address_book_show_' . $type . '_address_button', true ) ) : ?>
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
				href="<?php echo esc_url( $this->get_address_book_endpoint_url( $name, $type ) ); ?>"
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
		<?php endif; ?>

		<?php
	}

	/**
	 * Removes the link/button to add new addresses, if over the save limit in the settings.
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @since 2.2.1
	 */
	public function limit_saved_addresses( $type ) {
		$save_limit = get_option( 'woo_address_book_' . $type . '_save_limit', 0 );
		if ( empty( $save_limit ) ) {
			return true;
		}
		$customer_id  = get_current_user_id();
		$address_book = $this->get_address_book( $customer_id, $type );

		if ( count( $address_book ) < $save_limit ) {
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
	public function get_address_book_endpoint_url( $address_book, $type ) {
		$url = wc_get_endpoint_url( 'edit-address', $type, get_permalink() );
		return add_query_arg( 'address-book', $address_book, $url );
	}

	/**
	 * Returns the next available shipping address name.
	 *
	 * @param string $address_names - An array of saved address names.
	 * @param string $type - 'billing' or 'shipping'.
	 * @since 1.0.0
	 */
	public function set_new_address_name( $address_names, $type ) {

		// Check the address book entries and add a new one.
		if ( ! empty( $address_names ) && is_array( $address_names ) ) {
			// Find the first address name that doesn't exist.
			$counter = 2;
			do {
				$name = $type . $counter;
				$counter++;
			} while ( in_array( $name, $address_names, true ) );
		} else { // Start the address book.

			$name = $type;
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
		wc_get_template( 'myaccount/my-address-book.php', array( 'type' => $type ), '', plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' );
	}

	/**
	 * Modify the address field to allow for available countries to displayed correctly. Overrides
	 * most of woocommerce_form_field().
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
	public function address_country_select( $field, $key, $args, $value ) {
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
		* HALL EDIT: The primary purpose for this override is to match the additional shipping
		* billing addresses in addition to the default addresses.
		*/
		$countries = preg_match( '/shipping[0-9]*_country/', $key ) ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

		if ( 1 === count( $countries ) ) {
			$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

			$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';
		} else {
			$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . '><option value="">' . esc_html__( 'Select a country / region&hellip;', 'woocommerce' ) . '</option>';

			foreach ( $countries as $ckey => $cvalue ) {
				$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
			}

			$field .= '</select>';

			$field .= '<noscript><button type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country / region', 'woocommerce' ) . '">' . esc_html__( 'Update country / region', 'woocommerce' ) . '</button></noscript>';
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
	 * Process the saving to the address book on Address Save in My Account.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $name - The name of the address being updated.
	 */
	public function update_address_names( $user_id, $name ) {
		if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
			return;
		}

		$type = $name;
		if ( isset( $_GET['address-book'] ) ) {
			$name = trim( sanitize_text_field( wp_unslash( $_GET['address-book'] ) ), '/' );
		}

		$this->add_address_name( $user_id, $name, $type );
	}

	/**
	 * Add new Address to Address Book if it doesn't exist.
	 *
	 * @since 1.7.2
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $name - The name of the address being updated.
	 * @param string $type - 'billing' or 'shipping'.
	 */
	private function add_address_name( $user_id, $name, $type ) {

		// Get the address book and update the label.
		$address_names = $this->get_address_names( $user_id, $type );

		// Build new array if one does not exist.
		if ( ! is_array( $address_names ) || empty( $address_names ) ) {
			$address_names = array();
		}

		// Add address name if not already in array.
		if ( ! in_array( $name, $address_names, true ) ) {
			array_push( $address_names, $name );
			$this->save_address_names( $user_id, $address_names, $type );
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
	 * @param int    $user_id - User's ID.
	 * @param string $type - 'billing' or 'shipping'.
	 * @return array
	 */
	public function get_address_names( $user_id, $type ) {
		if ( ! isset( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$address_names = get_user_meta( $user_id, 'wc_address_book_' . $type, true );
		if ( empty( $address_names ) ) {
			if ( 'shipping' === $type ) {
				// Check for shipping addresses saved in pre 2.0 format.
				$address_names = get_user_meta( $user_id, 'wc_address_book', true );
				if ( is_array( $address_names ) ) {
					// Save addresses in the new format.
					$this->save_address_names( $user_id, $address_names, 'shipping' );
					return $address_names;
				}
				$shipping_address = get_user_meta( $user_id, 'shipping_address_1', true );
				// Return just a default shipping address if no other addresses are saved.
				if ( ! empty( $shipping_address ) ) {
					return array( 'shipping' );
				}
			}
			if ( 'billing' === $type ) {
				$billing_address = get_user_meta( $user_id, 'billing_address_1', true );
				// Return just a default billing address if no other addresses are saved.
				if ( ! empty( $billing_address ) ) {
					return array( 'billing' );
				}
			}

			// If we don't have an address, just return an empty array.
			return array();
		}

		// Downgrade from 3.0 if in that format.
		if ( isset( $address_names['addresses'] ) ) {
			$address_names = $address_names['addresses'];
			foreach ( $address_names as $address_name ) {
				if ( strpos( $address_name, $type ) !== false ) {
					$address_data = get_user_meta( $user_id, 'wc_address_book_address_' . $type . '_' . $address_name, true );
					if ( ! empty( $address_data ) ) {
						foreach ( $address_data as $key => $value ) {
							if ( ! empty( $value ) ) {
								update_user_meta( $user_id, $address_name . '_' . $key, $value );
							}
						}
					}
				}
			}
			update_user_meta( $user_id, 'wc_address_book_' . $type, $address_names );
		}

		return $address_names;
	}

	/**
	 * Returns an array of the customer's addresses with field values.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $type - 'billing' or 'shipping'.
	 * @return array
	 */
	public function get_address_book( $user_id, $type ) {
		$countries = new WC_Countries();

		if ( ! isset( $country ) ) {
			$country = $countries->get_base_country();
		}

		if ( ! isset( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$address_names = $this->get_address_names( $user_id, $type );

		$address_fields = WC()->countries->get_address_fields( $country, $type . '_' );

		// Get the set address fields, including any custom values.
		$address_keys = array_keys( $address_fields );

		$address_book = array();

		if ( ! empty( $address_names ) ) {
			foreach ( $address_names as $name ) {
				if ( strpos( $name, $type ) === false ) {
					continue;
				}

				$address = array();

				foreach ( $address_keys as $field ) {

					// Remove the default name so the custom ones can be added.
					$field = str_replace( $type, '', $field );

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
	 * @param int    $user_id User's ID.
	 * @param array  $new_value Address book names.
	 * @param string $type - 'billing' or 'shipping'.
	 */
	public function save_address_names( $user_id, $new_value, $type ) {

		// Make sure that is a new_value to save.
		if ( ! isset( $new_value ) && ! isset( $type ) ) {
			return;
		}

		// Update the value.
		update_user_meta( $user_id, 'wc_address_book_' . $type, $new_value );
	}

	/**
	 * Adds the address book select to the checkout page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields An array of WooCommerce Address fields.
	 * @return array
	 */
	public function checkout_address_select_field( $fields ) {
		if ( is_user_logged_in() ) {
			foreach ( $fields as $type => $address_fields ) {
				if ( ( 'billing' === $type && $this->get_wcab_option( 'billing_enable' ) === true ) || ( 'shipping' === $type && $this->get_wcab_option( 'shipping_enable' ) === true ) ) {
					$address_book = $this->get_address_book( null, $type );
					$under_limit  = $this->limit_saved_addresses( $type );

					$select_type = 'select';
					if ( $this->get_wcab_option( 'use_radio_input', 'no' ) === true ) {
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

					if ( ! empty( $address_book ) && false !== $address_book ) {
						$is_subscription_renewal = $this->is_subscription_renewal();

						if ( $is_subscription_renewal ) {
							$default_to_new_address = false;
						} else {
							$default_to_new_address = $this->get_wcab_option( $type . '_default_to_new_address', false );
						}

						foreach ( $address_book as $name => $address ) {
							if ( ! empty( $address[ $name . '_address_1' ] ) ) {
								$address_selector[ $type . '_address_book' ]['options'][ $name ] = $this->address_select_label( $address, $name );
							}
						}

						if ( $is_subscription_renewal ) {
							$address_selector[ $type . '_address_book' ]['class'][] = 'wc-address-book-subscription-renewal';
						}

						if ( $under_limit ) {
							$address_selector[ $type . '_address_book' ]['options']['add_new'] = __( 'Add New Address', 'woo-address-book' );
						}

						if ( true === $default_to_new_address ) {
							$address_selector[ $type . '_address_book' ]['default'] = 'add_new';
						} else {
							$address_selector[ $type . '_address_book' ]['default'] = $type;
						}

						$fields[ $type ] = $address_selector + $fields[ $type ];
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Adds the address book select to the checkout page.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $address An array of WooCommerce Address data.
	 * @param string $name Name of the address field to use.
	 * @return string
	 */
	public function address_select_label( $address, $name ) {
		$label = '';

		$address_nickname = get_user_meta( get_current_user_id(), $name . '_address_nickname', true );
		if ( $address_nickname ) {
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
		if ( ! empty( $address[ $name . '_address_2' ] ) ) {
			if ( ! empty( $label ) ) {
				$label .= ', ';
			}
			$label .= $address[ $name . '_address_2' ];
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
	 */
	public function wc_address_book_delete() {
		check_ajax_referer( 'woo-address-book-delete', 'nonce' );

		if ( ! isset( $_POST['name'] ) ) {
			die( 'no address passed' );
		}

		$address_name  = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$type          = $this->get_address_type( $address_name );
		$customer_id   = get_current_user_id();
		$address_book  = $this->get_address_book( $customer_id, $type );
		$address_names = $this->get_address_names( $customer_id, $type );

		foreach ( $address_book as $name => $address ) {
			if ( $address_name === $name ) {

				// Remove address from address book.
				$key = array_search( $name, $address_names, true );
				if ( ( $key ) !== false ) {
					unset( $address_names[ $key ] );
				}

				$this->save_address_names( $customer_id, $address_names, $type );

				// Remove specific address values.
				foreach ( $address as $field => $value ) {
					delete_user_meta( $customer_id, $field );
				}

				break;
			}
		}

		die();
	}

	/**
	 * Used for setting the primary addresses from the my-account page.
	 *
	 * @since 1.0.0
	 */
	public function wc_address_book_make_primary() {
		check_ajax_referer( 'woo-address-book-primary', 'nonce' );

		$customer_id = get_current_user_id();

		if ( ! isset( $_POST['name'] ) ) {
			die( 'no address passed' );
		}

		$alt_address_name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$type             = $this->get_address_type( $alt_address_name );
		$address_book     = $this->get_address_book( $customer_id, $type );

		$primary_address_name = $type;

		// Loop through and swap values between names.
		foreach ( $address_book[ $primary_address_name ] as $field => $value ) {
			$alt_field = preg_replace( '/^[^_]*_\s*/', $alt_address_name . '_', $field );
			update_user_meta( $customer_id, $field, $address_book[ $alt_address_name ][ $alt_field ] );
		}

		foreach ( $address_book[ $alt_address_name ] as $field => $value ) {
			$primary_field = preg_replace( '/^[^_]*_\s*/', $primary_address_name . '_', $field );
			update_user_meta( $customer_id, $field, $address_book[ $primary_address_name ][ $primary_field ] );
		}

		die();
	}

	/**
	 * Used for updating addresses dynamically on the checkout page.
	 *
	 * @since 1.0.0
	 */
	public function wc_address_book_checkout_update() {
		check_ajax_referer( 'woo-address-book-checkout', 'nonce' );

		global $woocommerce;

		if ( isset( $_POST['name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		} else {
			$name = 'add_new';
		}

		if ( isset( $_POST['type'] ) ) {
			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		} else {
			$type = 'shipping';
		}

		$address_book = $this->get_address_book( null, $type );

		if ( 'billing' === $type ) {
			$countries = $woocommerce->countries->get_allowed_countries();
		} elseif ( 'shipping' === $type ) {
			$countries = $woocommerce->countries->get_shipping_countries();
		}

		$response = array();

		// Get address field values.
		if ( 'add_new' !== $name ) {
			foreach ( $address_book[ $name ] as $field => $value ) {
				$field = preg_replace( '/^[^_]*_\s*/', $type . '_', $field );

				$response[ $field ] = $value;
			}
		} else {

			// If only one country is available, include it in the blank form.
			if ( 1 === count( $countries ) ) {
				$response[ $type . '_country' ] = key( $countries );
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Update the customer data with the information entered on checkout.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $update_customer_data - Toggles whether Woo should update customer data on checkout. This plugin overrides that function entirely.
	 * @param object  $checkout_object - An object of the checkout fields and values.
	 *
	 * @return boolean
	 */
	public function woocommerce_checkout_update_customer_data( $update_customer_data, $checkout_object ) {
		$billing_name            = isset( $_POST['billing_address_book'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_address_book'] ) ) : false;
		$shipping_name           = isset( $_POST['shipping_address_book'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address_book'] ) ) : false;
		$customer_id             = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
		$update_customer_data    = false;
		$ignore_shipping_address = true;

		if ( isset( $_POST['ship_to_different_address'] ) ) {
			$ignore_shipping_address = false;
		}

		// Name new address and update address book.
		if ( $this->get_wcab_option( 'billing_enable' ) === true ) {
			if ( 'add_new' === $billing_name || false === $billing_name ) {
				$address_names = $this->get_address_names( $customer_id, 'billing' );
				$billing_name  = $this->set_new_address_name( $address_names, 'billing' );
				$this->add_address_name( $customer_id, $billing_name, 'billing' );
			}
		} else {
			$billing_name = 'billing';
		}

		if ( $this->get_wcab_option( 'shipping_enable' ) === true ) {
			if ( ( 'add_new' === $shipping_name || false === $shipping_name ) && false === $ignore_shipping_address ) {
				$address_names = $this->get_address_names( $customer_id, 'shipping' );
				$shipping_name = $this->set_new_address_name( $address_names, 'shipping' );
				$this->add_address_name( $customer_id, $shipping_name, 'shipping' );
			}
		} else {
			$shipping_name = 'shipping';
		}

		$data = $checkout_object->get_posted_data();

		$customer = new WC_Customer( $customer_id );

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

		foreach ( $data as $key => $value ) {
			// Prevent address book and label fields from being written to the DB.
			if ( in_array( $key, array( 'address_book', 'address_label', 'shipping_address_book', 'shipping_address_label', 'billing_address_book', 'billing_address_label' ), true ) ) {
				continue;
			}

			// Prevent shipping keys from updating when ignoring shipping address.
			if ( 0 === stripos( $key, 'shipping_' ) && $ignore_shipping_address ) {
				continue;
			}

			// Store custom meta.
			if ( 'shipping' !== $shipping_name && 0 === stripos( $key, 'shipping_' ) ) {
				$key = str_replace( 'shipping_', $shipping_name . '_', $key );
				$customer->update_meta_data( $key, $value );
			} elseif ( 'billing' !== $billing_name && 0 === stripos( $key, 'billing_' ) ) {
				$key = str_replace( 'billing_', $billing_name . '_', $key );
				$customer->update_meta_data( $key, $value );
			} elseif ( is_callable( array( $customer, "set_{$key}" ) ) ) {
				// Use setters where available.
				$customer->{"set_{$key}"}( $value );

				// Store custom fields prefixed with shipping_ or billing_.
			} elseif ( 0 === stripos( $key, 'billing_' ) || 0 === stripos( $key, 'shipping_' ) ) {
				$customer->update_meta_data( $key, $value );
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
			$args['id'] = preg_replace( '/^billing[^_]+/', 'billing', $args['id'] );
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
		if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) {
			return;
		}

		if ( isset( $_GET['address-book'] ) ) {
			$name = trim( sanitize_text_field( wp_unslash( $_GET['address-book'] ) ), '/' );
			if ( isset( $_POST[ $name . '_country' ] ) ) {
				// Copy to shipping_country to bypass the check in save address.
				$type                        = $this->get_address_type( $name );
				$_POST[ $type . '_country' ] = sanitize_text_field( wp_unslash( $_POST[ $name . '_country' ] ) );
			}
		}
	}

	/**
	 * Replace the standard address key with address book key.
	 *
	 * @since 1.1.0
	 *
	 * @param array $address_fields - The set of WooCommerce Address Fields.
	 * @return array
	 */
	public function replace_address_key( $address_fields ) {
		if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$address_book = sanitize_text_field( wp_unslash( $_GET['address-book'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$type = $this->get_address_type( $address_book );

			$user_id       = get_current_user_id();
			$address_names = $this->get_address_names( $user_id, $type );

			// If a version of the address name exists with a slash, use it. Otherwise, trim the slash.
			// Previous versions of this plugin was including the slash in the address name.
			// While not causing problems, it should not have happened in the first place.
			// This enables backward compatibility.
			if ( in_array( $address_book, $address_names, true ) ) {
				$name = $address_book;
			} else {
				$name = trim( $address_book, '/' );
			}

			foreach ( $address_fields as $key => $value ) {
				$new_key = str_replace( $type, esc_attr( $name ), $key );

				$address_fields[ $new_key ] = $value;
				unset( $address_fields[ $key ] );
			}
		}

		return $address_fields;
	}

	/**
	 * Add Address Nickname fields to billing address fields.
	 *
	 * @param array $address_fields Current Address fields.
	 * @return array
	 *
	 * @since 1.8.0
	 */
	public function add_billing_address_nickname_field( $address_fields ) {
		if ( ! isset( $address_fields['billing_address_nickname'] ) ) {
			$address_fields['billing_address_nickname'] = array(
				'label'        => __( 'Address nickname', 'woo-address-book' ),
				'required'     => false,
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'given-name',
				'priority'     => -1,
				'value'        => '',
				'description'  => __( 'Will help you identify your addresses easily.', 'woo-address-book' ),
				'validate'     => array( 'address-nickname' ),
			);
		}

		return $address_fields;
	}

	/**
	 * Checks for non-primary Address Book address and doesn't show the checkbox to update subscriptions.
	 *
	 * @param array $address_fields Current Address fields.
	 * @return array
	 */
	public function remove_address_subscription_update_box( $address_fields ) {
		if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			remove_action( 'woocommerce_after_edit_address_form_billing', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
			remove_action( 'woocommerce_after_edit_address_form_shipping', 'WC_Subscriptions_Addresses::maybe_add_edit_address_checkbox', 10 );
		}

		return $address_fields;
	}

	/**
	 * Checks if this is a subscription renewal order.
	 *
	 * @return bool
	 */
	public function is_subscription_renewal() {
		if ( ! function_exists( 'wcs_get_order_type_cart_items' ) ) {
			return false;
		}
		return isset( WC()->cart ) && count( wcs_get_order_type_cart_items( 'renewal' ) ) === count( WC()->cart->get_cart() );
	}

	/**
	 * Add Address Nickname fields to shipping address fields.
	 *
	 * @param array $address_fields Current Address fields.
	 * @return array
	 *
	 * @since 1.8.0
	 */
	public function add_shipping_address_nickname_field( $address_fields ) {
		if ( ! isset( $address_fields['shipping_address_nickname'] ) ) {
			$address_fields['shipping_address_nickname'] = array(
				'label'        => __( 'Address nickname', 'woo-address-book' ),
				'required'     => false,
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'given-name',
				'priority'     => -1,
				'value'        => '',
				'description'  => __( 'Will help you identify your addresses easily. Suggested nicknames: Home, Work...', 'woo-address-book' ),
				'validate'     => array( 'address-nickname' ),
			);
		}

		return $address_fields;
	}

	/**
	 * Add address validation filter to the nickname that is dynamic based on address name.
	 *
	 * @return void
	 */
	public function validate_address_nickname_filter() {
		if ( is_wc_endpoint_url( 'edit-address' ) ) {
			if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
				return;
			}

			if ( ! empty( $_GET['address-book'] ) ) {
				$address_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
				$type         = $this->get_address_type( $address_name );
				add_filter( 'woocommerce_process_myaccount_field_' . $address_name . '_address_nickname', array( $this, 'validate_' . $type . '_address_nickname' ), 10, 1 );
			} else {
				add_filter( 'woocommerce_process_myaccount_field_billing_address_nickname', array( $this, 'validate_billing_address_nickname' ), 10, 1 );
				add_filter( 'woocommerce_process_myaccount_field_shipping_address_nickname', array( $this, 'validate_shipping_address_nickname' ), 10, 1 );
			}
		}
	}

	/**
	 * Perform validation on the Billing Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @return string|bool
	 *
	 * @since 1.8.0
	 */
	public function validate_billing_address_nickname( $new_nickname ) {
		if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
			return;
		}

		$current_address_name = 'billing';
		if ( ! empty( $_GET['address-book'] ) ) {
			$current_address_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
		}

		$address_names = get_user_meta( get_current_user_id(), 'wc_address_book_billing', true );

		if ( is_array( $address_names ) ) {
			foreach ( $address_names as $address_name ) {
				if ( $current_address_name !== $address_name ) {
					$address_nickname = get_user_meta( get_current_user_id(), $address_name . '_address_nickname', true );

					if ( ! empty( $new_nickname ) && sanitize_title( $address_nickname ) === sanitize_title( $new_nickname ) ) {
						// address nickname should be unique.
						wc_add_notice( __( 'Address nickname should be unique, another address is using the nickname.', 'woo-address-book' ), 'error' );
						$new_nickname = false;
						break;
					}
				}
			}
		}

		return mb_strtoupper( $new_nickname, 'UTF-8' );
	}

	/**
	 * Perform validation on the Shipping Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @return string|bool
	 *
	 * @since 1.8.0
	 */
	public function validate_shipping_address_nickname( $new_nickname ) {
		if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
			return;
		}

		$current_address_name = 'shipping';
		if ( ! empty( $_GET['address-book'] ) ) {
			$current_address_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
		}

		$address_names = get_user_meta( get_current_user_id(), 'wc_address_book_shipping', true );

		if ( is_array( $address_names ) ) {
			foreach ( $address_names as $address_name ) {
				if ( $current_address_name !== $address_name ) {
					$address_nickname = get_user_meta( get_current_user_id(), $address_name . '_address_nickname', true );

					if ( ! empty( $new_nickname ) && sanitize_title( $address_nickname ) === sanitize_title( $new_nickname ) ) {
						// address nickname should be unique.
						wc_add_notice( __( 'Address nickname should be unique, another address is using the nickname.', 'woo-address-book' ), 'error' );
						$new_nickname = false;
						break;
					}
				}
			}
		}

		return mb_strtoupper( $new_nickname, 'UTF-8' );
	}

	/**
	 * Perform the replacement of the localized format with the data.
	 *
	 * @param array $address Address Formats.
	 * @param array $args Address Data.
	 * @return array
	 */
	public function address_nickname_field_replacement( $address, $args ) {
		$address['{address_nickname}'] = '';

		if ( ! empty( $args['address_nickname'] ) ) {
			$address['{address_nickname}'] = $args['address_nickname'];
		}

		return $address;
	}

	/**
	 * Prefix address formats with the address nickname.
	 *
	 * @param array $formats All of the country formats.
	 * @return array
	 */
	public function address_nickname_localization_format( $formats ) {
		foreach ( $formats as $iso_code => $format ) {
			$formats[ $iso_code ] = "{address_nickname}\n" . $format;
		}

		return $formats;
	}

	/**
	 * Get the address nickname to add it to the formatted address data.
	 *
	 * @param array  $fields Address fields.
	 * @param int    $customer_id Customer to get address for.
	 * @param string $type Which address to get.
	 * @return array
	 */
	public function get_address_nickname( $fields, $customer_id, $type ) {
		if ( substr( $type, 0, 8 ) === 'shipping' || substr( $type, 0, 7 ) === 'billing' ) {
			$fields['address_nickname'] = get_user_meta( $customer_id, $type . '_address_nickname', true );
		}

		return $fields;
	}

	/**
	 * Don't show Address Nickname field on the checkout if the option is configured not to.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function remove_nickname_field_from_checkout( $fields ) {
		if ( isset( $fields['shipping']['shipping_address_nickname'] ) && ! $this->get_wcab_option( 'shipping_address_nickname_checkout', 'no' ) ) {
			unset( $fields['shipping']['shipping_address_nickname'] );
		}

		if ( isset( $fields['billing']['billing_address_nickname'] ) && ! $this->get_wcab_option( 'billing_address_nickname_checkout', 'no' ) ) {
			unset( $fields['billing']['billing_address_nickname'] );
		}

		return $fields;
	}

	/**
	 * Show an input to be able to get the shipping_calc setting into javascript.
	 */
	public function woocommerce_before_checkout_shipping_form() {
		echo '<input type="hidden" id="woocommerce_enable_shipping_calc" value="' . esc_attr( get_option( 'woocommerce_enable_shipping_calc' ) ) . '" />';
	}

	/**
	 * Get the nonce value from the request for the given name.
	 *
	 * @param string $name Request name of nonce.
	 * @return string
	 */
	private function nonce_value( $name ) {
		if ( ! isset( $_REQUEST[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		return $_REQUEST[ $name ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
	}

}
