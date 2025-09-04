<?php

use function CrossPeakSoftware\WooCommerce\AddressBook\current_user_id;

/**
 * WooCommerce Address Book.
 *
 * Legacy deprecated class for backwards compatibility.
 *
 * @class    WC_Address_Book
 * @version  2.6.2
 * @deprecated 3.0.0
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
		$this->version = \CrossPeakSoftware\WooCommerce\AddressBook\PLUGIN_VERSION;
	} // end constructor

	/**
	 * Load plugin option.
	 *
	 * @since 2.0.0
	 * @deprecated 3.0.0
	 *
	 * @param string $name The option name.
	 * @param string $default The default value for the setting.
	 * @return boolean
	 */
	public function get_wcab_option( $name, $default = 'yes' ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
		$this->deprecated_function(
			'WC_Address_Book::get_wcab_option',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Settings\setting( $name, $default );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.6.0
	 * @deprecated 3.0.0
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
	 * @deprecated 3.0.0
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$this->deprecated_function(
			'WC_Address_Book::load_plugin_textdomain',
			'\CrossPeakSoftware\WooCommerce\AddressBook\General\load_plugin_textdomain'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\General\load_plugin_textdomain();
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 * @since 1.0.0
	 */
	public function activate( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->deprecated_function(
			'WC_Address_Book::activate'
		);
		flush_rewrite_rules();
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 * @since 1.0.0
	 */
	public function deactivate( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->deprecated_function(
			'WC_Address_Book::deactivate'
		);
		flush_rewrite_rules();
	}

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param boolean $network_wide - True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 * @since 1.0.0
	 */
	public function uninstall( $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$this->deprecated_function(
			'WC_Address_Book::uninstall'
		);
		flush_rewrite_rules();
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function scripts_styles() {
		$this->deprecated_function(
			'WC_Address_Book::scripts_styles',
			'\CrossPeakSoftware\WooCommerce\AddressBook\General\scripts_styles'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\General\scripts_styles();
	}

	/**
	 * Get address type from name
	 *
	 * @param string $name Address name.
	 *
	 * @since 1.8.0
	 * @deprecated 3.0.0
	 */
	public function get_address_type( $name ) {
		$this->deprecated_function(
			'WC_Address_Book::get_address_type'
		);
		$type = preg_replace( '/\d/', '', $name );
		return $type;
	}

	/**
	 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function add_additional_address_button( $type ) {
		$this->deprecated_function(
			'WC_Address_Book::add_additional_address_button',
			'\CrossPeakSoftware\WooCommerce\AddressBook\add_additional_address_button'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\add_additional_address_button( (string) $type );
	}

	/**
	 * Removes the link/button to add new addresses, if over the save limit in the settings.
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @since 2.2.1
	 * @deprecated 3.0.0
	 */
	public function limit_saved_addresses( $type ) {
		$this->deprecated_function(
			'WC_Address_Book::limit_saved_addresses',
			'\CrossPeakSoftware\WooCommerce\AddressBook\limit_saved_addresses'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\limit_saved_addresses( (string) $type );
	}

	/**
	 * Get the address book edit endpoint URL.
	 *
	 * @param string $address_book Address book name.
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @deprecated 3.0.0
	 *
	 * @return string
	 */
	public function get_address_book_endpoint_url( $address_book, $type ) {
		$this->deprecated_function(
			'WC_Address_Book::get_address_book_endpoint_url',
			'\CrossPeakSoftware\WooCommerce\AddressBook\get_address_book_endpoint_url'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\get_address_book_endpoint_url( (string) $address_book, (string) $type );
	}

	/**
	 * Returns the next available shipping address name.
	 *
	 * @param array  $address_names - An array of saved address names.
	 * @param string $type - 'billing' or 'shipping'.
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function set_new_address_name( $address_names, $type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::set_new_address_name',
			'\CrossPeakSoftware\WooCommerce\AddressBook\get_new_address_number'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\get_new_address_number( $address_names );
	}

	/**
	 * Replace My Address with the Address Book to My Account Menu.
	 *
	 * @param array $items - An array of menu items.
	 * @return array
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function wc_address_book_add_to_menu( $items ) {
		$this->deprecated_function(
			'WC_Address_Book::wc_address_book_add_to_menu',
			'\CrossPeakSoftware\WooCommerce\AddressBook\add_to_menu'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\add_to_menu( $items );
	}

	/**
	 * Adds Address Book Content.
	 *
	 * @param string $type - The type of address.
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function wc_address_book_page( $type ) {
		$this->deprecated_function(
			'WC_Address_Book::wc_address_book_page',
			'\CrossPeakSoftware\WooCommerce\AddressBook\address_page'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\address_page( $type );
	}

	/**
	 * Modify the address field to allow for available countries to displayed correctly. Overrides
	 * most of woocommerce_form_field().
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param mixed  $args Arguments.
	 * @param string $value (default: null).
	 * @return string
	 */
	public function address_country_select( $field, $key, $args, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::address_country_select'
		);
		return $field;
	}

	/**
	 * Process the saving to the address book on Address Save in My Account.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $name - The name of the address being updated.
	 */
	public function update_address_names( $user_id, $name ) {
		$this->deprecated_function(
			'WC_Address_Book::update_address_names'
		);
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
	 * @deprecated 3.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $name - The name of the address being updated.
	 * @param string $type - 'billing' or 'shipping'.
	 */
	private function add_address_name( $user_id, $name, $type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::add_address_name'
		);
	}

	/**
	 * Redirect to the Edit Address page on save. Overrides the default redirect to /my-account/
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $name - The name of the address being updated.
	 */
	public function redirect_on_save( $user_id, $name ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::redirect_on_save'
		);
		if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( 'edit-address' ) );
			exit;
		}
	}

	/**
	 * Returns an array of the customer's address names.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $type - 'billing' or 'shipping'.
	 * @return array
	 */
	public function get_address_names( $user_id, $type ) {
		if ( empty( $user_id ) ) {
			$user_id = current_user_id( 'WC_Address_Book::get_address_names' );
		}
		$this->deprecated_function(
			'WC_Address_Book::get_address_names',
			'\CrossPeakSoftware\WooCommerce\AddressBook\AddressBook'
		);
		$customer      = new \WC_Customer( $user_id );
		$address_names = $customer->get_meta( 'wc_address_book_' . $type );
		return $address_names['addresses'] ?? array();
	}

	/**
	 * Returns an array of the customer's addresses with field values.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param int    $user_id - User's ID.
	 * @param string $type - 'billing' or 'shipping'.
	 * @return array
	 */
	public function get_address_book( $user_id, $type ) {
		if ( empty( $user_id ) ) {
			$user_id = current_user_id( 'WC_Address_Book::get_address_book' );
		}
		$this->deprecated_function(
			'WC_Address_Book::get_address_book',
			'\CrossPeakSoftware\WooCommerce\AddressBook\get_address_book'
		);
		$customer = new \WC_Customer( $user_id );
		return \CrossPeakSoftware\WooCommerce\AddressBook\get_address_book( $customer, $type )->addresses();
	}

	/**
	 * Returns an array of the users/customer additional address key value pairs.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
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
		// No equivalent.
		$this->deprecated_function(
			'WC_Address_Book::save_address_names'
		);
	}

	/**
	 * Adds the address book select to the checkout page.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param array $fields An array of WooCommerce Address fields.
	 * @return array
	 */
	public function checkout_address_select_field( $fields ) {
		$this->deprecated_function(
			'WC_Address_Book::checkout_address_select_field',
			'\CrossPeakSoftware\WooCommerce\AddressBook\checkout_address_select_field'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\checkout_address_select_field( $fields );
	}

	/**
	 * Adds the address book select to the checkout page.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param array  $address An array of WooCommerce Address data.
	 * @param string $name Name of the address field to use.
	 * @return string
	 */
	public function address_select_label( $address, $name ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::address_select_label',
			'\CrossPeakSoftware\WooCommerce\AddressBook\address_select_label'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\address_select_label( $address );
	}

	/**
	 * Used for deleting addresses from the my-account page.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function wc_address_book_delete() {
		$this->deprecated_function(
			'WC_Address_Book::wc_address_book_delete',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\delete'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\delete();
	}

	/**
	 * Used for setting the primary addresses from the my-account page.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function wc_address_book_make_primary() {
		$this->deprecated_function(
			'WC_Address_Book::wc_address_book_make_primary',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\make_default'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\make_default();
	}

	/**
	 * Used for updating addresses dynamically on the checkout page.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 */
	public function wc_address_book_checkout_update() {
		$this->deprecated_function(
			'WC_Address_Book::wc_address_book_checkout_update',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\checkout_update'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\Ajax\checkout_update();
	}

	/**
	 * Update the customer data with the information entered on checkout.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param boolean $update_customer_data - Toggles whether Woo should update customer data on checkout. This plugin overrides that function entirely.
	 * @param object  $checkout_object - An object of the checkout fields and values.
	 *
	 * @return boolean
	 */
	public function woocommerce_checkout_update_customer_data( $update_customer_data, $checkout_object ) {
		$this->deprecated_function(
			'WC_Address_Book::woocommerce_checkout_update_customer_data',
			'\CrossPeakSoftware\WooCommerce\AddressBook\woocommerce_checkout_update_customer_data'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\woocommerce_checkout_update_customer_data( $update_customer_data, $checkout_object );
	}

	/**
	 * Standardize the address edit fields to match Woo's IDs.
	 *
	 * @since 1.0.0
	 * @deprecated 3.0.0
	 *
	 * @param array  $args - The set of arguments being passed to the field.
	 * @param string $key - The name of the address being edited.
	 * @param string $value - The value a field will be prepopulated with.
	 * @return array
	 */
	public function standardize_field_ids( $args, $key, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->deprecated_function(
			'WC_Address_Book::standardize_field_ids'
		);
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
	 * @deprecated 3.0.0
	 */
	public function before_save_address() {
		$this->deprecated_function(
			'WC_Address_Book::before_save_address'
		);
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
	 * @deprecated 3.0.0
	 *
	 * @param array $address_fields - The set of WooCommerce Address Fields.
	 * @return array
	 */
	public function replace_address_key( $address_fields ) {
		$this->deprecated_function(
			'WC_Address_Book::replace_address_key'
		);
		if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$address_book = sanitize_text_field( wp_unslash( $_GET['address-book'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$type = $this->get_address_type( $address_book );

			$user_id       = current_user_id( 'WC_Address_Book::replace_address_key' );
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
	 * @deprecated 3.0.0
	 */
	public function add_billing_address_nickname_field( $address_fields ) {
		$this->deprecated_function(
			'WC_Address_Book::add_billing_address_nickname_field',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\add_billing_address_nickname_field'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\add_billing_address_nickname_field( $address_fields );
	}

	/**
	 * Checks for non-primary Address Book address and doesn't show the checkbox to update subscriptions.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param array $address_fields Current Address fields.
	 * @return array
	 */
	public function remove_address_subscription_update_box( $address_fields ) {
		$this->deprecated_function(
			'WC_Address_Book::remove_address_subscription_update_box',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions\remove_address_subscription_update_box'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions\remove_address_subscription_update_box( $address_fields );
	}

	/**
	 * Checks if this is a subscription renewal order.
	 *
	 * @deprecated 3.0.0
	 *
	 * @return bool
	 */
	public function is_subscription_renewal() {
		$this->deprecated_function(
			'WC_Address_Book::is_subscription_renewal',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions\is_subscription_renewal'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Subscriptions\is_subscription_renewal();
	}

	/**
	 * Add Address Nickname fields to shipping address fields.
	 *
	 * @param array $address_fields Current Address fields.
	 * @return array
	 *
	 * @since 1.8.0
	 * @deprecated 3.0.0
	 */
	public function add_shipping_address_nickname_field( $address_fields ) {
		$this->deprecated_function(
			'WC_Address_Book::add_shipping_address_nickname_field',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\add_shipping_address_nickname_field'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\add_shipping_address_nickname_field( $address_fields );
	}

	/**
	 * Add address validation filter to the nickname that is dynamic based on address name.
	 *
	 * @deprecated 3.0.0
	 *
	 * @return void
	 */
	public function validate_address_nickname_filter() {
		$this->deprecated_function(
			'WC_Address_Book::validate_address_nickname_filter'
		);
	}

	/**
	 * Perform validation on the Billing Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @return string|bool
	 *
	 * @since 1.8.0
	 * @deprecated 3.0.0
	 */
	public function validate_billing_address_nickname( $new_nickname ) {
		$this->deprecated_function(
			'WC_Address_Book::validate_billing_address_nickname',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\validate_billing_address_nickname'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\validate_billing_address_nickname( $new_nickname );
	}

	/**
	 * Perform validation on the Shipping Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @return string|bool
	 *
	 * @since 1.8.0
	 * @deprecated 3.0.0
	 */
	public function validate_shipping_address_nickname( $new_nickname ) {
		$this->deprecated_function(
			'WC_Address_Book::validate_shipping_address_nickname',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\validate_shipping_address_nickname'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\validate_shipping_address_nickname( $new_nickname );
	}

	/**
	 * Perform the replacement of the localized format with the data.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param array $address Address Formats.
	 * @param array $args Address Data.
	 * @return array
	 */
	public function address_nickname_field_replacement( $address, $args ) {
		$this->deprecated_function(
			'WC_Address_Book::address_nickname_field_replacement',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\address_nickname_field_replacement'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\address_nickname_field_replacement( $address, $args );
	}

	/**
	 * Prefix address formats with the address nickname.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param array $formats All of the country formats.
	 * @return array
	 */
	public function address_nickname_localization_format( $formats ) {
		$this->deprecated_function(
			'WC_Address_Book::address_nickname_localization_format',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\address_nickname_localization_format'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\address_nickname_localization_format( $formats );
	}

	/**
	 * Get the address nickname to add it to the formatted address data.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param array  $fields Address fields.
	 * @param int    $customer_id Customer to get address for.
	 * @param string $type Which address to get.
	 * @return array
	 */
	public function get_address_nickname( $fields, $customer_id, $type ) {
		$this->deprecated_function(
			'WC_Address_Book::get_address_nickname',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\get_address_nickname'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\get_address_nickname( $fields, $customer_id, $type );
	}

	/**
	 * Don't show Address Nickname field on the checkout if the option is configured not to.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function remove_nickname_field_from_checkout( $fields ) {
		$this->deprecated_function(
			'WC_Address_Book::remove_nickname_field_from_checkout',
			'\CrossPeakSoftware\WooCommerce\AddressBook\Nickname\remove_nickname_field_from_checkout'
		);
		return \CrossPeakSoftware\WooCommerce\AddressBook\Nickname\remove_nickname_field_from_checkout( $fields );
	}

	/**
	 * Show an input to be able to get the shipping_calc setting into javascript.
	 *
	 * @deprecated 3.0.0
	 */
	public function woocommerce_before_checkout_shipping_form() {
		$this->deprecated_function(
			'WC_Address_Book::woocommerce_before_checkout_shipping_form',
			'\CrossPeakSoftware\WooCommerce\AddressBook\woocommerce_before_checkout_shipping_form'
		);
		\CrossPeakSoftware\WooCommerce\AddressBook\woocommerce_before_checkout_shipping_form();
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

	/**
	 * Report deprecated function.
	 *
	 * @param string $function_name Name of function.
	 * @param string $replacement Name of replacement function.
	 * @return void
	 */
	private function deprecated_function( $function_name, $replacement = '' ) {
		if ( function_exists( 'wc_deprecated_function' ) ) {
			wc_deprecated_function( $function_name, '3.0', $replacement );
		} else {
			_deprecated_function( esc_html( $function_name ), '3.0', esc_html( $replacement ) );
		}
	}
}
