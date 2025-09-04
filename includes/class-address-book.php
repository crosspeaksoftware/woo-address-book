<?php
/**
 * WooCommerce Address Book.
 *
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Address_Book
 *
 * @package WooCommerce Address Book
 */
class Address_Book {


	/**
	 * The Customer object.
	 *
	 * @var \WC_Customer
	 */
	protected \WC_Customer $customer;

	/**
	 * The address type.
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * The default address.
	 *
	 * @var string
	 */
	protected ?string $default = null;

	/**
	 * An array of addresses.
	 *
	 * @var array
	 */
	protected array $addresses = array();

	/**
	 * An array of address keys.
	 *
	 * @var array
	 */
	protected array $address_keys = array();

	/**
	 * Automatic save
	 *
	 * @var bool
	 */
	protected bool $automatic_save = true;

	/**
	 * AddressBook constructor.
	 *
	 * @param \WC_Customer $customer The Customer object.
	 * @param string       $type - 'billing' or 'shipping'.
	 */
	public function __construct( \WC_Customer $customer, string $type ) {
		$this->customer = $customer;
		$this->type     = $type;
		$this->hydrate_address_names();
	}

	/**
	 * Get the address book addresses.
	 *
	 * @return array
	 */
	public function addresses() {
		// Hydrate the addresses.
		$addresses = array();
		foreach ( $this->address_keys as $key ) {
			$addresses[ $key ] = $this->address( $key );
		}
		/**
		 * Modify the address book addresses.
		 *
		 * @since 3.0.0
		 *
		 * @param array        $addresses The address book addresses.
		 * @param \WC_Customer $customer The Customer object.
		 * @param string       $type - 'billing' or 'shipping'.
		 * @param Address_Book $address_book The address book.
		 */
		return apply_filters( 'wc_address_book_addresses', $addresses, $this->customer, $this->type, $this );
	}


	/**
	 * Get the address.
	 *
	 * @param string $key The address key.
	 * @return array
	 */
	public function address( $key ) {
		if ( isset( $this->addresses[ $key ] ) ) {
			return $this->addresses[ $key ];
		}
		$address = $this->customer->get_meta( 'wc_address_book_address_' . $this->type . '_' . $key );
		if ( empty( $address ) ) {
			$address = get_woo_address( $this->customer, $key, $this->type );

			if ( ! empty( $address ) && ! empty( array_filter( $address ) ) ) {
				$this->update( $key, $address );

				if ( 'billing' !== $key && 'shipping' !== $key ) {
					// Delete legacy address book meta data from versions prior to 3.0.
					$customer_id = $this->customer->get_id();
					foreach ( $address as $address_field_key => $value ) {
						delete_user_meta( $customer_id, $key . '_' . $address_field_key );
					}
				}
			}
		}
		// convert any nulls to empty strings.
		foreach ( $address as $field => $value ) {
			if ( is_null( $value ) ) {
				$address[ $field ] = '';
			} elseif ( is_array( $value ) ) {
				$address[ $field ] = implode( ', ', $value );
			}
		}

		$this->addresses[ $key ] = $address;
		return $this->addresses[ $key ];
	}

	/**
	 * Get the default key.
	 *
	 * @return ?string
	 */
	public function default_key() {
		return $this->default;
	}

	/**
	 * Get the default address.
	 *
	 * @return ?array
	 */
	public function default() {
		if ( is_null( $this->default ) ) {
			return null;
		}
		return $this->address( $this->default );
	}

	/**
	 * Check if it is the default key.
	 *
	 * @param string $key The address key.
	 * @return bool
	 */
	public function is_default( ?string $key ) {
		return $this->default === $key;
	}


	/**
	 * Disable Automatic Save
	 *
	 * @return self
	 */
	public function disable_automatic_save() {
		$this->automatic_save = false;
		return $this;
	}

	/**
	 * Set the default address.
	 *
	 * @param string $key The address key.
	 * @return ?string
	 */
	public function set_default( ?string $key ) {
		if ( $this->has( $key ) ) {
			$this->default = $key;
			$address       = $this->address( $key );

			// Get address fields from country.
			$address_fields = WC()->countries->get_address_fields( $address['country'] ?? ( new \WC_Countries() )->get_base_country(), $this->type . '_' );
			$address_keys   = array_keys( $address_fields );
			$address_keys   = array_merge( $address_keys, array( $this->type . '_address_nickname' ) );

			// Loop through and set to default address.
			foreach ( $address_keys as $key ) {
				try {
					$address_field_key = substr( $key, strlen( $this->type ) + 1 );
					$function          = 'set_' . ltrim( $key, '_' );

					if ( is_callable( array( $this->customer, $function ) ) ) {
						$this->customer->{$function}( $address[ $address_field_key ] ?? '' );
					} else {
						$this->customer->update_meta_data( $key, $address[ $address_field_key ] ?? '' );
					}
				} catch ( \WC_Data_Exception $e ) {
					// Set notices. Ignore invalid billing email, since is already validated.
					if ( 'customer_invalid_billing_email' !== $e->getErrorCode() ) {
						wc_add_notice( $e->getMessage(), 'error' );
					}
				}
			}
			$this->update_address_book_meta();
			return $key;
		}
		return null;
	}

	/**
	 * Get the number of addresses.
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->address_keys );
	}

	/**
	 * Check if it has the address.
	 *
	 * @param string $key The address key.
	 * @return bool
	 */
	public function has( $key ) {
		return array_search( $key, $this->address_keys, true ) !== false;
	}

	/**
	 * Get the current address limit.
	 * Use 0 for unlimited.
	 *
	 * @return int
	 */
	public function limit() {
		$save_limit = (int) get_option( 'woo_address_book_' . $this->type . '_save_limit', 0 );
		/**
		 * Filters the number of saved addresses.
		 *
		 * @since 3.1.0
		 * @param int $save_limit Number of addresses that are able to be saved.
		 * @param string $type - 'billing' or 'shipping'.
		 *
		 * @return int Use 0 for unlimited.
		 */
		return (int) apply_filters( 'woo_address_book_limit', $save_limit, $this->type );
	}

	/**
	 * Check if we are under the address limit.
	 *
	 * @return bool
	 */
	public function is_under_limit() {
		/**
		 * Filters whether we are under the address limit.
		 *
		 * @since 3.1.0
		 * @param ?boolean $preempt Whether to preempt the limit. Default null.
		 * @param string $type - 'billing' or 'shipping'.
		 *
		 * @return ?boolean true if under the limit, false if over. null to continue checking as normal.
		 */
		$preempt = apply_filters( 'woo_address_book_is_under_limit', null, $this->type );
		if ( ! is_null( $preempt ) ) {
			return $preempt;
		}
		$save_limit = $this->limit();
		if ( empty( $save_limit ) ) {
			return true;
		}
		if ( $this->count() < $save_limit ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the current Address book type.
	 *
	 * @return string
	 */
	public function type() {
		return $this->type;
	}

	/**
	 * Check if the address book is for billing.
	 *
	 * @return boolean
	 */
	public function is_billing() {
		return 'billing' === $this->type;
	}

	/**
	 * Check if the address book is for shipping.
	 *
	 * @return boolean
	 */
	public function is_shipping() {
		return 'shipping' === $this->type;
	}

	/**
	 * Save the address to the customer's profile
	 *
	 * @param string $key The key of the address.
	 * @param array  $address The address array.
	 * @return void
	 */
	public function update( string $key, array $address ) {
		$this->update_address_meta( $key, $address );
		if ( $this->is_default( $key ) ) {
			$this->set_default( $key );
		}
		$this->save_customer();
	}

	/**
	 * Add the address to the customer's profile
	 *
	 * @param array $address The address array.
	 * @param bool  $as_default Whether to set this address as the default.
	 * @return string      The address key.
	 */
	public function add( array $address, bool $as_default = false ) {
		$key                  = $this->get_new_address_number();
		$this->address_keys[] = $key;
		$this->update_address_meta( $key, $address );
		if ( $as_default ) {
			$this->set_default( $key );
		}
		$this->update_address_book_meta();
		return $key;
	}

	/**
	 * Delete Address from Address Book
	 *
	 * @param string $key The address key.
	 * @return void
	 */
	public function delete( string $key ) {
		if ( isset( $this->addresses[ $key ] ) ) {
			unset( $this->addresses[ $key ] );
		}
		$index = array_search( $key, $this->address_keys, true );
		if ( false !== $index ) {
			unset( $this->address_keys[ $index ] );
		}
		$this->customer->delete_meta_data( 'wc_address_book_address_' . $this->type . '_' . $key );
		$this->update_address_book_meta();
	}

	/**
	 * Save the address book to the customer's profile
	 *
	 * @return int
	 */
	public function save() {
		return $this->customer->save();
	}

	/**
	 * Hydrate the address names.
	 *
	 * @return void
	 */
	protected function hydrate_address_names() {
		$address_names = $this->customer->get_meta( 'wc_address_book_' . $this->type );
		if ( empty( $address_names ) ) {
			if ( 'shipping' === $this->type ) {
				// Check for shipping addresses saved in pre 2.0 format.
				$address_names = $this->customer->get_meta( 'wc_address_book' );
				if ( is_array( $address_names ) ) {
					// Save the new format.
					$this->address_keys = $address_names;
					$this->default      = $this->type;
					$this->update_address_book_meta();
					return;
				}
			}
			$address = get_woo_address( $this->customer, $this->type, $this->type );
			// Return just a default address if no other addresses are saved.
			if ( ! empty( $address ) && ! empty( array_filter( $address ) ) ) {
				$this->add( $address, true );
				return;
			}
		}
		// Upgrade addresses from prior to 3.0.
		if ( ! isset( $address_names['addresses'] ) && ! empty( $address_names ) && is_array( $address_names ) ) {
			$this->address_keys = $address_names;
			$this->default      = $this->type;
			$this->update_address_book_meta();
			return;
		}

		$this->address_keys = $address_names['addresses'] ?? array();
		$this->default      = $address_names['default'] ?? null;
	}

	/**
	 * Returns the next available shipping address name.
	 *
	 * @return string
	 */
	protected function get_new_address_number() {
		// Check the address book entries and add a new one.
		if ( ! empty( $this->address_keys ) ) {
			// Find the first address number that doesn't exist.
			$counter = 1;
			while ( in_array( "a$counter", $this->address_keys, true ) ) {
				++$counter;
			}
			return "a$counter";
		}
		return 'a1';
	}

	/**
	 * Update the address meta data
	 *
	 * @param string $key The address key.
	 * @param array  $address The address array.
	 * @return void
	 */
	protected function update_address_meta( string $key, ?array $address = null ) {
		if ( is_array( $address ) ) {
			$this->addresses[ $key ] = $address;
		}
		$this->customer->update_meta_data( 'wc_address_book_address_' . $this->type . '_' . $key, $this->addresses[ $key ] );
	}

	/**
	 * Update the address book meta data
	 *
	 * @return int The customer id.
	 */
	protected function update_address_book_meta() {
		$address_book = array(
			'addresses' => $this->address_keys,
			'default'   => $this->default,
		);
		$this->customer->update_meta_data( 'wc_address_book_' . $this->type, $address_book );
		return $this->save_customer();
	}

	/**
	 * Save the address book to the customer's profile
	 *
	 * @return int
	 */
	protected function save_customer() {
		if ( ! $this->automatic_save ) {
			return 0;
		}
		return $this->customer->save();
	}
}
