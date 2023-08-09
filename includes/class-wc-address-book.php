<?php
/**
 * WooCommerce Address Book.
 *
 * @class    WC_Address_Book
 * @version  3.0.0
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
		$this->version = '3.0.0';

		// Enqueue Styles and Scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );

		// Load Plugin Textdomain for localization.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add custom address fields.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_address_select_field' ), 9999, 1 );

		// AJAX action to delete an address.
		add_action( 'wp_ajax_wc_address_book_delete', array( $this, 'ajax_delete' ) );

		// AJAX action to set a default address.
		add_action( 'wp_ajax_wc_address_book_make_default', array( $this, 'ajax_make_default' ) );

		// AJAX action to refresh the address at checkout.
		add_action( 'wp_ajax_wc_address_book_checkout_update', array( $this, 'ajax_checkout_update' ) );

		// Update the customer data with the information entered on checkout.
		add_filter( 'woocommerce_checkout_update_customer_data', array( $this, 'woocommerce_checkout_update_customer_data' ), 10, 2 );

		add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'woocommerce_before_checkout_shipping_form' ) );

		// Add Address Book to Menu.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_to_menu' ), 10 );
		add_action( 'woocommerce_account_edit-address_endpoint', array( $this, 'address_page' ), 20 );

		// Get the address book to load into the edit form.
		add_filter( 'woocommerce_address_to_edit', array( $this, 'address_to_edit' ), 1000, 2 );

		// Replace the WooCommerce Save Address with our own function, since it doesn't have the hooks needed to override it.
		add_action( 'template_redirect', array( $this, 'save_address_form_handler' ), 9 );
		remove_action( 'template_redirect', array( 'WC_Form_Handler', 'save_address' ) );

		// WooCommerce Subscriptions support.
		add_filter( 'woocommerce_billing_fields', array( $this, 'remove_address_subscription_update_box' ), 10, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'remove_address_subscription_update_box' ), 10, 1 );

		// Adds support for address nicknames.
		add_filter( 'woocommerce_billing_fields', array( $this, 'add_billing_address_nickname_field' ), 10, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'add_shipping_address_nickname_field' ), 10, 1 );
		add_filter( 'woocommerce_process_myaccount_field_billing_address_nickname', array( $this, 'validate_billing_address_nickname' ), 10, 1 );
		add_filter( 'woocommerce_process_myaccount_field_shipping_address_nickname', array( $this, 'validate_shipping_address_nickname' ), 10, 1 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'address_nickname_field_replacement' ), 10, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_nickname_localization_format' ), -10 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'get_address_nickname' ), 10, 3 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_nickname_field_from_checkout' ) );

		add_action( 'rest_api_init', array( $this, 'wc_api_get_addresses_endpoint' ) );

		// Add Address Book export endpoint.
		add_action( 'init', array( $this, 'export_endpoint' ) );
		// Handle csv import.
		add_action( 'init', array( $this, 'handle_address_import' ) );
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
	 * @return   self  A single instance of this class.
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
				'delete_security'     => wp_create_nonce( 'woo-address-book-delete' ),
				'default_security'    => wp_create_nonce( 'woo-address-book-default' ),
				'checkout_security'   => wp_create_nonce( 'woo-address-book-checkout' ),
				'delete_confirmation' => __( 'Are you sure you want to delete this address?', 'woo-address-book' ),
				'default_text'        => __( 'Default', 'woo-address-book' ),
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
	 * Adds a link/button to the my account page under the addresses for adding additional addresses to their account.
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 *
	 * @since 1.0.0
	 */
	public function add_additional_address_button( $type ) {
		$under_limit = $this->limit_saved_addresses( $type );

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
				href="<?php echo esc_url( $this->get_address_book_endpoint_url( 'new', $type ) ); ?>"
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
	 * @since 2.2.1
	 */
	public function limit_saved_addresses( $type ) {
		$save_limit = get_option( 'woo_address_book_' . $type . '_save_limit', 0 );
		if ( empty( $save_limit ) ) {
			return true;
		}
		$customer           = new WC_Customer( get_current_user_id() );
		$address_book_names = $this->get_address_names( $customer, $type );
		if ( empty( $address_book_names['addresses'] ) || count( $address_book_names['addresses'] ) < $save_limit ) {
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
	 * @since 1.0.0
	 */
	public function get_new_address_number( $address_names ) {
		// Check the address book entries and add a new one.
		if ( ! empty( $address_names ) && is_array( $address_names ) ) {
			// Find the first address number that doesn't exist.
			$counter = 1;
			while ( in_array( "a$counter", $address_names, true ) ) {
				$counter++;
			}
			return "a$counter";
		}
		return 'a1';
	}

	/**
	 * Replace My Address with the Address Book to My Account Menu.
	 *
	 * @param array $items - An array of menu items.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_menu( $items ) {
		foreach ( $items as $key => $value ) {
			if ( 'edit-address' === $key ) {
				$items[ $key ] = __( 'Address book', 'woo-address-book' );
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
	public function address_page( $type ) {
		wc_get_template( 'myaccount/my-address-book.php', array( 'type' => $type ), '', plugin_dir_path( dirname( __FILE__ ) ) . 'templates/' );
	}

	/**
	 * Address Book export endpoint.
	 *
	 * @since 3.0.0
	 */
	public function export_endpoint() {
		if ( ! isset( $_GET['wc-address-book-export-type'] ) ) {
			return;
		}
		$type = wp_unslash( $_GET['wc-address-book-export-type'] );
		if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
			return;
		}

		$customer_id = get_current_user_id();
		if ( empty( $customer_id ) ) {
			return;
		}
		$customer = new WC_Customer( $customer_id );
		if ( ! $customer ) {
			return;
		}

		header( 'Content-type: text/csv' );
		header( 'Cache-Control: no-store, no-cache' );
		header( 'Content-Disposition: attachment; filename="woo-address-book_' . $type . '.csv"' );

		$this->print_addresses_as_csv( $this->get_address_book( $customer, $type ) );

		exit;
	}

	/**
	 * Adds a link/button to the my account for Address Book export.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type - 'billing' or 'shipping'.
	 */
	public function add_wc_address_book_export_button( $type ) {
		if ( 'billing' === $type ) :
			?>
		<div class="">
			<a href="<?php echo esc_url( $this->get_export_endpoint_url( $type ) ); ?>" class="button button-full-width"><?php echo esc_html_e( 'Export all Billing Addresses', 'woo-address-book' ); ?></a>
		</div>
			<?php
		endif;

		if ( 'shipping' === $type ) :
			?>
		<div class="">
			<a href="<?php echo esc_url( $this->get_export_endpoint_url( $type ) ); ?>" class="button button-full-width"><?php echo esc_html_e( 'Export all Shipping Addresses', 'woo-address-book' ); ?></a>
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
	public function get_export_endpoint_url( $type ) {
		$url = wc_get_endpoint_url( 'address-book-export', '', get_permalink() );

		return add_query_arg( array( 'wc-address-book-export-type' => $type ), $url );
	}

	/**
	 * Format addresses as csv file
	 *
	 * @since 3.0.0
	 *
	 * @param array $addresses_array
	 */
	public function print_addresses_as_csv( $addresses_array ) {
		$addresses = array();
		$keys      = array();

		foreach ( $addresses_array['addresses'] as $address ) {
			foreach ( array_keys( $address ) as $key ) {
				if ( ! in_array( $key, $keys ) ) {
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
				fputcsv( $csv, $row );
			}
		}
		fclose( $csv );
	}

	/**
	 * Parse uploaded csv file
	 *
	 * @since 3.0.0
	 *
	 * @param array  $file The uploaded file to be parsed.
	 * @param string $type The type, 'billing' or 'shipping'.
	 * @param string $delimiter The csv delimiter. Defaults to ','.
	 * @return array|false
	 */
	public function parse_csv_file( $file, $type, $delimiter = ',' ) {
		if ( isset( $file['error'] ) && 0 === $file['error'] ) {
			$extension = pathinfo( wc_clean( wp_unslash( $file['name'] ) ) )['extension']; // phpcs:ignore PHPCompatibility.Syntax.NewFunctionArrayDereferencing.Found
			$tmp_name  = $file['tmp_name'];

			if ( 'csv' === $extension ) {
				if ( ( $handle = fopen( $tmp_name, 'r' ) ) !== false ) {
					$first_row = true;
					$csv       = array();
					$keys      = array();

					while ( ( $data = fgetcsv( $handle, 10000, $delimiter ) ) !== false ) {
						if ( $first_row ) {
							// Values in the first row are used as key names.
							$keys      = array_values( $data );
							$first_row = false;
						} else {
							$row = array();
							foreach ( $keys as $i => $key ) {
								$row[ $type . '_' . $key ] = $data[ $i ] ?? '';
							}
							$csv[] = $row;
						}
					}

					fclose( $handle );

					return $csv;
				}
			} else {
				wc_add_notice( __( 'Invalid file type!', 'woo-address-book' ), 'error' );
			}
		} else {
			wc_add_notice( __( 'Error uploading file!', 'woo-address-book' ), 'error' );
		}
		return false;
	}

	/**
	 * Import parsed addresses
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Customer $customer The customer object.
	 * @param string      $type - 'billing' or 'shipping'.
	 * @param array       $parsed_data The data to be imported.
	 *
	 * @return array|bool
	 */
	public function import_addresses( $customer, $type, $parsed_data ) {
		$import_limit_setting = get_option( 'woo_address_book_' . $type . '_save_limit', 0 );
		$address_names        = $this->get_address_names( $customer, $type );

		if ( ! $parsed_data ) {
			return false;
		}
		$partial_import       = false;
		$address_book_changed = false;
		$counter              = 0;
		foreach ( $parsed_data as $data ) {
			if ( ! empty( $import_limit_setting ) && $import_limit_setting > 0 ) {
				$import_limit = $import_limit_setting - count( $address_names['addresses'] );
				if ( 0 >= $import_limit ) {
					$partial_import = true;
					break;
				}
			}

			$new_address_name = $this->get_new_address_number( $address_names['addresses'] );

			$this->save_address( $customer, $new_address_name, $type, $data );

			$address_book_changed         = true;
			$address_names['addresses'][] = $new_address_name;
			if ( empty( $address_names['default'] ) ) {
				$address_names['default'] = $new_address_name;
			}
			$counter++;
		}
		if ( $address_book_changed ) {
			$this->save_address_names( $customer, $address_names, $type );
		}
		return array(
			'partial_import'       => $partial_import,
			'address_book_changed' => $address_book_changed,
			'imported_count'       => $counter,
			'address_count'        => count( $parsed_data ),
		);
	}

	/**
	 * Handle the check for import of addresses.
	 *
	 * @since 3.0.0
	 */
	public function handle_address_import() {
		if ( isset( $_FILES['wc_address_book_upload_billing_csv'] ) ) {
			$type = 'billing';
		} elseif ( isset( $_FILES['wc_address_book_upload_shipping_csv'] ) ) {
			$type = 'shipping';
		} else {
			return;
		}

		if ( ! check_admin_referer( 'woo-address-book-' . $type . '-csv-import', 'woo-address-book_nonce' ) ) {
			return;
		}

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return;
		}
		$customer = new WC_Customer( $current_user_id );
		if ( ! $customer ) {
			return;
		}
		$parsed_data = $this->parse_csv_file( $_FILES[ 'wc_address_book_upload_' . $type . '_csv' ], $type, ',' );
		if ( $parsed_data ) {
			$importable_addresses = array();
			foreach ( $parsed_data as $key => $address ) {
				if ( empty( $address[ $type . '_country' ] ) ) {
					$address[ $type . '_country' ] = WC()->countries->get_base_country();
				}
				$address_fields = WC()->countries->get_address_fields( wc_clean( $address[ $type . '_country' ] ), $type . '_' );
				$address        = $this->validate_address( $address_fields, $address, $type );
				if ( empty( $address ) ) {
					wc_add_notice( __( 'Invalid address format.', 'woo-address-book' ), 'error' );
				}
				if ( 0 < wc_notice_count( 'error' ) ) {
					wc_add_notice( sprintf( __( 'Error while validating Address #%d of the csv file.', 'woo-address-book' ), $key + 1 ), 'error' );
					return;
				}
				$importable_addresses[] = $address;
			}
			$status = $this->import_addresses( $customer, $type, $importable_addresses );
			if ( false === $status ) {
				wc_add_notice( __( 'Error importing addresses!', 'woo-address-book' ), 'error' );
			} else {
				if ( $status['partial_import'] ) {
					if ( 'billing' === $type ) {
						wc_add_notice( sprintf( __( 'Imported %1$1d of %2$2d billing addresses. Billing Address Book is full!', 'woo-address-book' ), $status['imported_count'], $status['address_count'] ), 'notice' );
					} elseif ( 'shipping' === $type ) {
						wc_add_notice( sprintf( __( 'Imported %1$1d of %2$2d shipping addresses. Shipping Address Book is full!', 'woo-address-book' ), $status['imported_count'], $status['address_count'] ), 'notice' );
					}
				} else {
					if ( 'billing' === $type ) {
						wc_add_notice( sprintf( __( 'Imported %1d billing addresses successfully.', 'woo-address-book' ), $status['imported_count'] ), 'success' );
					} elseif ( 'shipping' === $type ) {
						wc_add_notice( sprintf( __( 'Imported %1d shipping addresses successfully.', 'woo-address-book' ), $status['imported_count'] ), 'success' );
					}
				}
				// Redirect to the address book page.
				wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
				exit;
			}
		}
	}

	/**
	 * Add new Address to Address Book if it doesn't exist.
	 *
	 * @since 1.7.2
	 *
	 * @param WC_Customer $customer The customer object.
	 * @param string      $name - The name of the address being updated.
	 * @param string      $type - 'billing' or 'shipping'.
	 * @return string
	 */
	private function add_address_name( $customer, $name, $type ) {
		// Get the address book and update the label.
		$address_names = $this->get_address_names( $customer, $type, false );

		// Build new array if one does not exist.
		if ( empty( $address_names ) || ! is_array( $address_names ) ) {
			$address_names = array(
				'addresses' => array(),
				'default'   => null,
			);
		}

		if ( ! isset( $address_names['addresses'] ) || ! is_array( $address_names['addresses'] ) ) {
			$address_names['addresses'] = array();
		}

		if ( ! isset( $address_names['default'] ) ) {
			$address_names['default'] = null;
		}

		if ( is_null( $name ) ) {
			$name = $this->get_new_address_number( $address_names['addresses'] );
		}

		// Add address name if not already in array.
		if ( ! in_array( $name, $address_names['addresses'], true ) ) {
			array_push( $address_names['addresses'], $name );
			if ( is_null( $address_names['default'] ) ) {
				$address_names['default'] = $name;
			}
			$this->save_address_names( $customer, $address_names, $type );
		}
		return $name;
	}

	/**
	 * Returns an array of the customer's address names.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Customer $customer The Customer object..
	 * @param string      $type - 'billing' or 'shipping'.
	 * @param boolean     $can_upgrade if it should search for upgradeable addresses.
	 * @return array
	 */
	public function get_address_names( $customer, $type, $can_upgrade = true ) {
		$address_names = $this->get_customer_meta( 'wc_address_book_' . $type, $customer );
		if ( $can_upgrade && empty( $address_names ) ) {
			if ( 'shipping' === $type ) {
				// Check for shipping addresses saved in pre 2.0 format.
				$address_names = $this->get_customer_meta( 'wc_address_book', $customer );
				if ( is_array( $address_names ) ) {
					// Save addresses in the new format.
					$new_address_names = array(
						'addresses' => $address_names,
						'default'   => null,
					);
					$this->save_address_names( $customer, $new_address_names, 'shipping' );
					return $new_address_names;
				}
			}
			$address = $this->get_woo_address( $customer, $type, $type );
			// Return just a default address if no other addresses are saved.
			if ( ! empty( $address ) ) {
				$name = $this->add_address_name( $customer, null, $type );
				$this->save_address( $customer, $name, $type, $address );
				$new_address_names = array(
					'addresses' => array( $name ),
					'default'   => $name,
				);
				return $new_address_names;
			}

			// If we don't have an address, just return an empty array.
			return array(
				'addresses' => array(),
				'default'   => null,
			);
		}
		// Upgrade addresses from prior to 3.0
		if ( ! isset( $address_names['addresses'] ) && ! empty( $address_names ) && is_array( $address_names ) ) {
			$address_names = array(
				'addresses' => $address_names,
				'default'   => $type,
			);
			$this->save_address_names( $customer, $address_names, $type );
		}

		return $address_names;
	}

	/**
	 * Returns an array of the customer's addresses with field values.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Customer $customer The Customer object.
	 * @param string      $type - 'billing' or 'shipping'.
	 * @return array
	 */
	public function get_address_book( $customer, $type ) {
		$address_names = $this->get_address_names( $customer, $type );

		$address_book = array(
			'addresses' => array(),
			'default'   => $address_names['default'] ?? null,
		);

		if ( ! empty( $address_names['addresses'] ) ) {
			foreach ( $address_names['addresses'] as $name ) {
				$address_book['addresses'][ $name ] = $this->get_address( $customer, $name, $type );
			}
		}

		return apply_filters( 'wc_address_book_addresses', $address_book );
	}

	/**
	 * Returns an array of the users/customer additional address key value pairs.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Customer $customer The Customer object.
	 * @param array       $new_value Address book names.
	 * @param string      $type - 'billing' or 'shipping'.
	 */
	public function save_address_names( $customer, $new_value, $type ) {
		$this->save_customer_meta( $customer, 'wc_address_book_' . $type, $new_value );
	}

	/**
	 * Save the address to the customer's profile
	 *
	 * @param WC_Customer $customer The Customer object.
	 * @param string      $key The key of the address.
	 * @param string      $type The type of address (billing or shipping)
	 * @param array       $address The address array.
	 * @return void
	 */
	public function save_address( $customer, $key, $type, $address ) {
		$this->save_customer_meta( $customer, 'wc_address_book_address_' . $type . '_' . $key, $address );
	}

	/**
	 * The the address specified for the customer.
	 *
	 * @param WC_Customer $customer The Customer object.
	 * @param string      $key The key of the address.
	 * @param string      $type The type of address (billing or shipping)
	 * @return array
	 */
	public function get_address( $customer, $key, $type ) {
		$address = $this->get_customer_meta( 'wc_address_book_address_' . $type . '_' . $key, $customer );
		if ( empty( $address ) ) {
			$address = $this->get_woo_address( $customer, $key, $type, true );

			if ( ! empty( $address ) ) {
				$this->save_address( $customer, $key, $type, $address );
			}
		}
		// convert any nulls to empty strings.
		foreach ( $address as $field => $value ) {
			if ( is_null( $value ) ) {
				$address[ $field ] = '';
			}
		}

		return $address;
	}

		/**
		 * The the address specified for the customer.
		 *
		 * @param WC_Customer $customer The Customer object.
		 * @param string      $key The key of the address.
		 * @param string      $type The type of address (billing or shipping)
		 * @param bool        $upgrade If this is a legacy version upgrade.
		 * @return array
		 */
	public function get_woo_address( $customer, $key, $type, $upgrade = false ) {
		// Fetch address from WooCommerce customer meta.
		$address_fields = WC()->countries->get_address_fields( ( new WC_Countries() )->get_base_country(), $type . '_' );

		$address_keys = array_keys( $address_fields );
		$address_keys = array_merge( $address_keys, array( $type . '_address_nickname' ) );

		$address = array();

		foreach ( $address_keys as $field ) {
			// Remove the default name so the custom ones can be added.
			$custom_field      = $key . substr( $field, strlen( $type ) );
			$address_field_key = substr( $field, strlen( $type ) + 1 );

			$address[ $address_field_key ] = $this->get_customer_meta( $custom_field, $customer );
			if ( $upgrade && 'billing' !== $key && 'shipping' !== $key ) {
				// Delete legacy address book meta data from versions prior to 3.0.
				if ( $customer ) {
					$customer_id = $customer->get_id();
				} else {
					$customer_id = get_current_user_id();
				}
				delete_user_meta( $customer_id, $custom_field );
			}
		}

		return $address;
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

					if ( ! empty( $address_book['addresses'] ) && false !== $address_book ) {
						$is_subscription_renewal = $this->is_subscription_renewal();

						if ( $is_subscription_renewal ) {
							$default_to_new_address = false;
						} else {
							$default_to_new_address = $this->get_wcab_option( $type . '_default_to_new_address', false );
						}

						foreach ( $address_book['addresses'] as $name => $address ) {
							$label = $this->address_select_label( $address );
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
	 * @param array $address An array of WooCommerce Address data.
	 * @return string
	 */
	public function address_select_label( $address ) {
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

		return apply_filters( 'wc_address_book_address_select_label', $label, $address );
	}

	/**
	 * Used for deleting addresses from the my-account page.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete() {
		check_ajax_referer( 'woo-address-book-delete', 'nonce' );

		if ( ! isset( $_POST['name'] ) || ! isset( $_POST['type'] ) ) {
			die( 'no address passed' );
		}

		$address_name  = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$type          = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		$customer      = new WC_Customer( get_current_user_id() );
		$address_names = $this->get_address_names( $customer, $type );

		if ( $address_name === $address_names['default'] ) {
			wc_add_notice( __( 'You cannot delete the default address, please make another address default first.', 'woo-address-book' ), 'error' );
			wc_print_notices();

			die();
		}

		$customer->delete_meta_data( 'wc_address_book_address_' . $type . '_' . $address_name );

		// Remove address from address book.
		$key = array_search( $address_name, $address_names['addresses'], true );
		if ( ( $key ) !== false ) {
			unset( $address_names['addresses'][ $key ] );
		}

		$this->save_address_names( $customer, $address_names, $type );

		wc_add_notice( __( 'Address deleted successfully.', 'woo-address-book' ) );
		wc_print_notices();

		die();
	}

	/**
	 * Used for setting the default addresses from the my-account page.
	 *
	 * @since 1.0.0
	 */
	public function ajax_make_default() {
		check_ajax_referer( 'woo-address-book-default', 'nonce' );

		$customer = new WC_Customer( get_current_user_id() );

		if ( ! $customer ) {
			return;
		}

		if ( ! isset( $_POST['name'] ) ) {
			die( 'no address passed' );
		}

		$alt_address_name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$type             = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		if ( ! in_array( $type, array( 'billing', 'shipping' ), true ) ) {
			die( 'invalid address type' );
		}

		$address = $this->get_address( $customer, $alt_address_name, $type );
		if ( empty( $address ) ) {
			die( 'no address found' );
		}

		// Loop through and set to default address.
		foreach ( $address as $field => $value ) {
			$this->save_customer_meta( $customer, $type . '_' . $field, $value, false );
		}

		$address_names = $this->get_address_names( $customer, $type );
		// Update default address.
		$address_names['default'] = $alt_address_name;
		$this->save_address_names( $customer, $address_names, $type );

		$customer->save();

		wc_add_notice( __( 'Default address updated successfully.', 'woo-address-book' ) );
		wc_print_notices();

		die();
	}

	/**
	 * Used for updating addresses dynamically on the checkout page.
	 *
	 * @since 1.0.0
	 */
	public function ajax_checkout_update() {
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

		$customer = new WC_Customer( get_current_user_id() );

		$address = $this->get_address( $customer, $name, $type );

		if ( 'billing' === $type ) {
			$countries = $woocommerce->countries->get_allowed_countries();
		} elseif ( 'shipping' === $type ) {
			$countries = $woocommerce->countries->get_shipping_countries();
		}

		$response = array();

		// Get address field values.
		if ( 'add_new' !== $name ) {
			if ( ! empty( $address ) ) {
				foreach ( $address as $field => $value ) {
					$response[ $type . '_' . $field ] = $value;
				}
			}
		} else {
			// If only one country is available, include it in the blank form.
			if ( 1 === count( $countries ) ) {
				$response[ $type . '_country' ] = key( $countries );
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

		$customer = new WC_Customer( $customer_id );

		// Name new address and update address book.
		if ( $this->get_wcab_option( 'billing_enable' ) === true ) {
			if ( 'add_new' === $billing_name || false === $billing_name ) {
				$billing_name = $this->add_address_name( $customer, null, 'billing' );
			}
		} else {
			$billing_name = 'billing';
		}

		if ( $this->get_wcab_option( 'shipping_enable' ) === true ) {
			if ( ( 'add_new' === $shipping_name || false === $shipping_name ) && false === $ignore_shipping_address ) {
				$shipping_name = $this->add_address_name( $customer, null, 'shipping' );
			}
		} else {
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
			// Prevent address book and label fields from being written to the DB.
			if ( in_array( $key, array( 'shipping_address_book', 'shipping_address_label', 'billing_address_book', 'billing_address_label' ), true ) ) {
				continue;
			}

			// Prevent shipping keys from updating when ignoring shipping address.
			if ( 0 === stripos( $key, 'shipping_' ) && $ignore_shipping_address ) {
				continue;
			}

			if ( 0 === stripos( $key, 'shipping_' ) ) {
				$key                                   = substr( $key, 9 ); // trim off the shipping_ prefix.
				$address_book_shipping_address[ $key ] = $value;
			} elseif ( 0 === stripos( $key, 'billing_' ) ) {
				$key                                  = substr( $key, 8 ); // trim off the billing_ prefix.
				$address_book_billing_address[ $key ] = $value;
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
		if ( ! empty( $address_book_billing_address ) ) {
			$address = $this->get_address( $customer, $billing_name, 'billing' );
			$address = array_merge( $address, $address_book_billing_address );
			$this->save_address( $customer, $billing_name, 'billing', $address );
		}
		if ( ! empty( $address_book_shipping_address ) ) {
			$address = $this->get_address( $customer, $shipping_name, 'shipping' );
			$address = array_merge( $address, $address_book_shipping_address );
			$this->save_address( $customer, $shipping_name, 'shipping', $address );
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
	 * Replace the WooCommerce Save Address form handler.
	 *
	 * Logic copied from woocommerce/includes/class-wc-form-handler.php
	 * save_address() method.
	 *
	 * @since 3.0.0
	 */
	public function save_address_form_handler() {
		if ( isset( $_GET['address-book'] ) ) {
			$address_book_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
			if ( 'new' === $address_book_name ) {
				$address_book_name = null;
			}
		} else {
			// Not an address book address, so load default address.
			$address_book_name = 'default';
		}
		global $wp;

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-edit-address-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

		if ( ! wp_verify_nonce( $nonce_value, 'woocommerce-edit_address' ) ) {
			return;
		}

		if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) {
			return;
		}
		// Change post action so that the WooCommerce save_address doesn't trigger.
		$_POST['action'] = 'edit_address_book';

		wc_nocache_headers();

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$customer = new WC_Customer( $user_id );

		if ( ! $customer ) {
			return;
		}

		$load_address = isset( $wp->query_vars['edit-address'] ) ? wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true ) : 'billing';

		if ( ! isset( $_POST[ $load_address . '_country' ] ) ) {
			return;
		}

		$address = WC()->countries->get_address_fields( wc_clean( wp_unslash( $_POST[ $load_address . '_country' ] ) ), $load_address . '_' );

		$address_values = $this->validate_address( $address, $_POST, $load_address );

		/**
		 * Hook: woocommerce_after_save_address_validation.
		 *
		 * Allow developers to add custom validation logic and throw an error to prevent save.
		 *
		 * @param int         $user_id User ID being saved.
		 * @param string      $load_address Type of address e.g. billing or shipping.
		 * @param array       $address The address fields.
		 * @param WC_Customer $customer The customer object being saved. @since 3.6.0
		 */
		do_action( 'woocommerce_after_save_address_validation', $user_id, $load_address, $address, $customer );

		if ( 0 < wc_notice_count( 'error' ) ) {
			return;
		}

		$address_names = $this->get_address_names( $customer, $load_address );

		if ( 'default' === $address_book_name ) {
			if ( ! empty( $address_names['default'] ) ) {
				$address_book_name = $address_names['default'];
			} else {
				$address_book_name = $this->add_address_name( $customer, null, $load_address );
			}
		}

		// Update the notice when adding a new address.
		if ( ! is_array( $address_names ) || empty( $address_names['addresses'] ) || ! in_array( $address_book_name, $address_names['addresses'], true ) ) {
			wc_add_notice( __( 'Address added successfully.', 'woo-address-book' ) );
		} else {
			wc_add_notice( __( 'Address changed successfully.', 'woocommerce' ) );
		}

		$address_book_name = $this->add_address_name( $customer, $address_book_name, $load_address );

		$this->save_address( $customer, $address_book_name, $load_address, $address_values );

		$address_names = $this->get_address_names( $customer, $load_address );
		// Save the WooCommerce default address if we are saving that one.
		if ( ! empty( $address_names['default'] ) && $address_book_name === $address_names['default'] ) {
			foreach ( $address_values as $key => $value ) {
				$this->save_customer_meta( $customer, $load_address . '_' . $key, $value, false );
			}
			$customer->save();
		}

		/**
		 * Hook: woocommerce_customer_save_address.
		 *
		 * Allow developers to perform additional actions when the customer address is saved.
		 *
		 * @param int         $user_id User ID being saved.
		 * @param string      $load_address Type of address e.g. billing or shipping.
		 */
		do_action( 'woocommerce_customer_save_address', $user_id, $load_address );

		wp_safe_redirect( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Validate the address fields.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $address The set of WooCommerce Address Fields.
	 * @param array  $values The set of values to validate.
	 * @param string $load_address Address type to load, billing or shipping.
	 * @return array
	 */
	public function validate_address( $address, $values, $load_address ) {
		$address_values = array();
		foreach ( $address as $key => $field ) {
			if ( ! isset( $field['type'] ) ) {
				$field['type'] = 'text';
			}

			// Get Value.
			if ( 'checkbox' === $field['type'] ) {
				$value = (int) isset( $values[ $key ] );
			} else {
				$value = isset( $values[ $key ] ) ? wc_clean( wp_unslash( $values[ $key ] ) ) : '';
			}

			// Hook to allow modification of value.
			$value = apply_filters( 'woocommerce_process_myaccount_field_' . $key, $value );

			// Validation: Required fields.
			if ( ! empty( $field['required'] ) && empty( $value ) ) {
				/* translators: %s: Field name. */
				wc_add_notice( sprintf( __( '%s is a required field.', 'woocommerce' ), $field['label'] ), 'error', array( 'id' => $key ) );
			}

			if ( ! empty( $value ) ) {
				// Validation and formatting rules.
				if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {
					foreach ( $field['validate'] as $rule ) {
						switch ( $rule ) {
							case 'postcode':
								$country = wc_clean( wp_unslash( $values[ $load_address . '_country' ] ) );
								$value   = wc_format_postcode( $value, $country );

								if ( '' !== $value && ! WC_Validation::is_postcode( $value, $country ) ) {
									switch ( $country ) {
										case 'IE':
											$postcode_validation_notice = __( 'Please enter a valid Eircode.', 'woocommerce' );
											break;
										default:
											$postcode_validation_notice = __( 'Please enter a valid postcode / ZIP.', 'woocommerce' );
									}
									wc_add_notice( $postcode_validation_notice, 'error' );
								}
								break;
							case 'phone':
								if ( '' !== $value && ! WC_Validation::is_phone( $value ) ) {
									/* translators: %s: Phone number. */
									wc_add_notice( sprintf( __( '%s is not a valid phone number.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
								}
								break;
							case 'email':
								$value = strtolower( $value );

								if ( ! is_email( $value ) ) {
									/* translators: %s: Email address. */
									wc_add_notice( sprintf( __( '%s is not a valid email address.', 'woocommerce' ), '<strong>' . $field['label'] . '</strong>' ), 'error' );
								}
								break;
						}
					}
				}
			}

			$book_key                    = substr( $key, strlen( $load_address . '_' ) );
			$address_values[ $book_key ] = $value;
		}
		return $address_values;
	}

	/**
	 * Replace the standard address with the one from the address book.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $address The set of WooCommerce Address Fields.
	 * @param string $load_address Address type to load, billing or shipping.
	 * @return array
	 */
	public function address_to_edit( $address, $load_address ) {
		if ( isset( $_GET['address-book'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$address_book = sanitize_text_field( wp_unslash( $_GET['address-book'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$customer = new WC_Customer( get_current_user_id() );
			if ( ! $customer ) {
				return $address;
			}
			$address_names = $this->get_address_names( $customer, $load_address );

			if ( 'new' !== $address_book && in_array( $address_book, $address_names['addresses'], true ) ) {
				$current_address = $this->get_address( $customer, $address_book, $load_address );
				// This is new address, blank out all values.
				foreach ( $address as $key => $settings ) {
					$current_key = str_replace( $load_address . '_', '', $key );
					if ( isset( $current_address[ $current_key ] ) ) {
						$address[ $key ]['value'] = $current_address[ $current_key ];
					} else {
						$address[ $key ]['value'] = null;
					}
				}
			} else {
				// This is new address, blank out all values.
				foreach ( $address as $key => $settings ) {
					// Leave email and phone alone since they are most likely the same.
					if ( ! empty( $settings['value'] ) && 'key' !== 'billing_email' && 'key' !== 'billing_phone' ) {
						$address[ $key ]['value'] = null;
					}
				}
			}
		}

		return $address;
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
	 * Checks for non-default Address Book address and doesn't show the checkbox to update subscriptions.
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
	 * Perform validation on the Billing Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @return string|bool
	 *
	 * @since 1.8.0
	 */
	public function validate_billing_address_nickname( $new_nickname ) {
		return $this->validate_address_nickname( $new_nickname, 'billing' );
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
		return $this->validate_address_nickname( $new_nickname, 'shipping' );
	}

	/**
	 * Perform validation on the Address Nickname field.
	 *
	 * @param string $new_nickname The nickname the user input.
	 * @param string $type billing or shipping.
	 *
	 * @since 3.0.0
	 */
	public function validate_address_nickname( $new_nickname, $type ) {
		if ( ! wp_verify_nonce( $this->nonce_value( 'woocommerce-edit-address-nonce' ), 'woocommerce-edit_address' ) ) {
			return;
		}

		$current_address_name = $type;
		if ( ! empty( $_GET['address-book'] ) ) {
			$current_address_name = sanitize_text_field( wp_unslash( $_GET['address-book'] ) );
		}
		$customer = new WC_Customer( get_current_user_id() );

		$address_book = $this->get_address_book( $customer, $type );

		if ( ! empty( $address_book['addresses'] ) && is_array( $address_book['addresses'] ) ) {
			foreach ( $address_book['addresses'] as $address_name => $address ) {
				if ( $current_address_name !== $address_name ) {
					$address_nickname = $address['address_nickname'];
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
	 * @param int    $user_id Customer to get address for.
	 * @param string $type Which address to get.
	 * @return array
	 */
	public function get_address_nickname( $fields, $user_id, $type ) {
		if ( isset( $fields['address_nickname'] ) ) {
			return $fields;
		}
		$customer      = new WC_Customer( $user_id );
		$address_names = $this->get_address_names( $user_id, $type );
		$address_name  = $type;
		if ( ! empty( $address_names['default'] ) ) {
			$address_name = $address_names['default'];
		}

		$address                    = $this->get_address( $customer, $type, $address_name );
		$fields['address_nickname'] = $address['address_nickname'] ?? '';

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

	/**
	 * Get the customer meta values. Use getters if they exist.
	 *
	 * @param  string          $key Meta Key.
	 * @param WC_Customer|int $customer The customer object or ID.
	 * @param  string          $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	public function get_customer_meta( $key, $customer = null, $context = 'view' ) {
		if ( is_null( $customer ) ) {
			$customer = get_current_user_id();
		}
		if ( is_numeric( $customer ) ) {
			$customer = new WC_Customer( $customer );
		}
		if ( ! $customer ) {
			return false;
		}
		$function = 'get_' . ltrim( $key, '_' );
		if ( is_callable( array( $customer, $function ) ) ) {
			return $customer->{$function}( $context );
		}
		return $customer->get_meta( $key, true, $context );
	}

	/**
	 * Save the customer meta value. To handle saving.
	 *
	 * @param WC_Customer|int $customer The customer object or ID.
	 * @param string          $key The meta key.
	 * @param mixed           $value The meta value to save.
	 * @param boolean         $save Whether to save the customer or not. Defaults to true.
	 * @return void
	 */
	public function save_customer_meta( $customer, $key, $value, $save = true ) {
		try {
			if ( is_numeric( $customer ) ) {
				$customer = new WC_Customer( $customer );
			}
			if ( ! $customer ) {
				return;
			}
			// Set prop in customer object.
			if ( is_callable( array( $customer, "set_$key" ) ) ) {
				$customer->{"set_$key"}( $value );
			} else {
				$customer->update_meta_data( $key, $value );
			}
			if ( $save ) {
				$customer->save();
			}
		} catch ( WC_Data_Exception $e ) {
			// Set notices. Ignore invalid billing email, since is already validated.
			if ( 'customer_invalid_billing_email' !== $e->getErrorCode() ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Add a custom endpoint for the WooCommerce REST API that returns all addresses for a customer.
	 */
	public function wc_api_get_addresses_endpoint() {
		register_rest_route(
			'wc/v3',
			'/customers/(?P<id>\d+)/addresses',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'wc_api_get_addresses' ),
				'permission_callback' => array( $this, 'wc_api_get_permissions_check' ),
			)
		);
	}

	/**
	 * Get the addresses for a customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	public function wc_api_get_addresses( $request ) {
		$user_id            = $request['id'];
		$customer           = new WC_Customer( $request['id'] );
		$billing_addresses  = $this->get_address_book( $customer, 'billing' );
		$shipping_addresses = $this->get_address_book( $customer, 'shipping' );
		$addresses          = array(
			'id'               => $user_id,
			'default_billing'  => $billing_addresses['default'] ?? null,
			'billing'          => $billing_addresses['addresses'] ?? array(),
			'default_shipping' => $shipping_addresses['default'] ?? null,
			'shipping'         => $shipping_addresses['addresses'] ?? array(),
		);
		return $addresses;
	}

	/**
	 * Check whether a given request has permission to read customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function wc_api_get_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! wc_rest_check_user_permissions( 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
}
