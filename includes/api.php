<?php
/**
 * WooCommerce Address Book.
 *
 * @class    WC_Address_Book
 * @package  WooCommerce Address Book
 */

namespace CrossPeakSoftware\WooCommerce\AddressBook\API;

use function CrossPeakSoftware\WooCommerce\AddressBook\get_address_book;

// Prevent direct access data leaks.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add a custom endpoint for the WooCommerce REST API that returns all addresses for a customer.
 */
function get_addresses_endpoint() {
	register_rest_route(
		'wc/v3',
		'/customers/(?P<id>\d+)/addresses',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\get_addresses',
			'permission_callback' => __NAMESPACE__ . '\permissions_check_read',
		)
	);

	register_rest_route(
		'wc/v3',
		'/customers/(?P<id>\d+)/addresses/(?P<address_type>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\get_addresses',
			'permission_callback' => __NAMESPACE__ . '\permissions_check_read',
		)
	);

	register_rest_route(
		'wc/v3',
		'/customers/(?P<id>\d+)/addresses/(?P<address_type>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\create_address',
			'permission_callback' => __NAMESPACE__ . '\permissions_check_create',
		)
	);

	register_rest_route(
		'wc/v3',
		'/customers/(?P<id>\d+)/addresses/(?P<address_type>[a-zA-Z0-9-]+)/(?P<address_id>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'PUT',
			'callback'            => __NAMESPACE__ . '\edit_address',
			'permission_callback' => __NAMESPACE__ . '\permissions_check_edit',
		)
	);

	register_rest_route(
		'wc/v3',
		'/customers/(?P<id>\d+)/addresses/(?P<address_type>[a-zA-Z0-9-]+)/(?P<address_id>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'DELETE',
			'callback'            => __NAMESPACE__ . '\delete_address',
			'permission_callback' => __NAMESPACE__ . '\permissions_check_delete',
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\get_addresses_endpoint' );

/**
 * Get the addresses for a customer.
 *
 * @param \WP_REST_Request $request Full details about the request.
 * @return \WP_REST_Response
 */
function get_addresses( $request ) {
	if ( ! empty( $request['address_type'] ) ) {
		$types = array( $request['address_type'] );
	} else {
		$types = array( 'billing', 'shipping' );
	}
	$user_id   = (int) $request['id'];
	$customer  = new \WC_Customer( $request['id'] );
	$addresses = array(
		'id' => $user_id,
	);
	foreach ( $types as $type ) {
		$address                         = get_address_book( $customer, $type );
		$addresses[ $type ]              = $address->addresses();
		$addresses[ $type . '_default' ] = $address->default_key();
	}

	return new \WP_REST_Response( $addresses );
}

/**
 * Create an address
 *
 * @param \WP_REST_Request $request Full details about the request.
 *
 * @return \WP_REST_Response|\WP_Error
 */
function create_address( $request ) {
	$data = $request->get_json_params();

	$user_id = $request['id'];
	$type    = $request['address_type'];
	if ( empty( $data ) ) {
		return new \WP_Error(
			'woocommerce_api_missing_address_data',
			/* translators: %s is the address type */
			sprintf( __( 'No %s data provided to create address.', 'woo-address-book' ), $type ),
			array( 'status' => 400 )
		);
	}

	// Checks with can create new users.
	if ( ! current_user_can( 'create_users' ) ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_create_address_book_address',
			__( 'You do not have permission to create this address', 'woo-address-book' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Filter address data for the API request.
	 *
	 * @param array $data An array of address data.
	 * @param string $type The address type.
	 * @param int $user_id The customer ID.
	 *
	 * @since 3.1
	 *
	 * @return array
	 */
	$data = apply_filters( 'woocommerce_api_create_address_book_data', $data, $type, $user_id );

	$customer = new \WC_Customer( $user_id );
	if ( ! $customer->get_id() ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_create_address_book_address',
			__( 'This resource cannot be created.', 'woo-address-book' ),
			array( 'status' => 400 )
		);
	}

	$address_book = get_address_book( $customer, $type );
	$key          = $address_book->add( $data );

	/**
	 * Action for after creating an address.
	 *
	 * @param int $user_id The customer ID.
	 * @param array $data An array of address data.
	 * @param string $type The address type.
	 * @param string $key The address key.
	 *
	 * @since 3.1
	 */
	do_action( 'woocommerce_api_create_address_book_address', $customer->get_id(), $data, $type, $key );

	return new \WP_REST_Response( array( $key => $address_book->address( $key ) ), 201 );
}

/**
 * Edit an address
 *
 * @param \WP_REST_Request $request Full details about the request.
 *
 * @return \WP_REST_Response|\WP_Error
 */
function edit_address( $request ) {
	$data = $request->get_json_params();

	$user_id    = $request['id'];
	$type       = $request['address_type'];
	$address_id = $request['address_id'];

	if ( empty( $data ) ) {
		return new \WP_Error(
			'woocommerce_api_missing_address_data',
			/* translators: %s is the address type */
			sprintf( __( 'No %s data provided to edit address.', 'woo-address-book' ), $type ),
			array( 'status' => 400 )
		);
	}

	// Checks with can edit users.
	if ( ! current_user_can( 'edit_users' ) ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_edit_address',
			__( 'You do not have permission to edit this address', 'woo-address-book' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Filter address data for the API request.
	 *
	 * @param array $data An array of address data.
	 * @param string $type The address type.
	 * @param int $user_id The customer ID.
	 *
	 * @since 3.1
	 *
	 * @return array
	 */
	$data = apply_filters( 'woocommerce_api_edit_address_book_data', $data, $type, $user_id );

	$customer = new \WC_Customer( $user_id );
	if ( ! $customer->get_id() ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_edit_address_book_address',
			__( 'Customer not found.', 'woo-address-book' ),
			array( 'status' => 404 )
		);
	}

	$address_book = get_address_book( $customer, $type );
	$address_book->update( $address_id, $data );

	/**
	 * Action for after editing an address.
	 *
	 * @param int $user_id The customer ID.
	 * @param array $data An array of address data.
	 * @param string $type The address type.
	 * @param string $address_id The address key.
	 *
	 * @since 3.1
	 */
	do_action( 'woocommerce_api_edit_address_book_address', $customer->get_id(), $data, $type, $address_id );

	return new \WP_REST_Response( array( $address_id => $address_book->address( $address_id ) ) );
}


/**
 * Delete an address
 *
 * @param \WP_REST_Request $request Full details about the request.
 *
 * @return \WP_REST_Response|\WP_Error
 */
function delete_address( $request ) {
	$user_id    = $request['id'];
	$type       = $request['address_type'];
	$address_id = $request['address_id'];

	// Checks with can edit users.
	if ( ! current_user_can( 'edit_users' ) ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_edit_address',
			__( 'You do not have permission to create this address', 'woo-address-book' ),
			array( 'status' => 401 )
		);
	}

	$customer = new \WC_Customer( $user_id );
	if ( ! $customer->get_id() ) {
		return new \WP_Error(
			'woocommerce_api_user_cannot_edit_address',
			__( 'This customer does not exist.', 'woo-address-book' ),
			array( 'status' => 400 )
		);
	}

	$address_book = get_address_book( $customer, $type );
	$address      = $address_book->address( $address_id );
	if ( empty( $address ) ) {
		return new \WP_Error(
			'woocommerce_api_address_not_found',
			__( 'Address not found.', 'woo-address-book' ),
			array( 'status' => 404 )
		);
	}
	$address_book->delete( $address_id );

	/**
	 * Action for after deleting an address.
	 *
	 * @param int $user_id The customer ID.
	 * @param string $type The address type.
	 * @param string $address_id The address key.
	 *
	 * @since 3.1
	 */
	do_action( 'woocommerce_api_delete_address_book_address', $customer->get_id(), $type, $address_id );

	return new \WP_REST_Response( '', 202 );
}

/**
 * Check whether a given request has permission to read customers.
 *
 * @param  \WP_REST_Request $request Full details about the request.
 * @return \WP_Error|boolean
 */
function permissions_check_read( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! wc_rest_check_user_permissions( 'read' ) ) {
		return new \WP_Error(
			'woocommerce_rest_cannot_view',
			__( 'Sorry, you cannot list resources.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return true;
}

/**
 * Check whether a given request has permission to create customers.
 *
 * @param  \WP_REST_Request $request Full details about the request.
 * @return \WP_Error|boolean
 */
function permissions_check_create( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! wc_rest_check_user_permissions( 'create' ) ) {
		return new \WP_Error(
			'woocommerce_rest_cannot_create',
			__( 'Sorry, you are not allowed to create resources.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return true;
}
/**
 * Check whether a given request has permission to edit customers.
 *
 * @param  \WP_REST_Request $request Full details about the request.
 * @return \WP_Error|boolean
 */
function permissions_check_edit( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! wc_rest_check_user_permissions( 'edit' ) ) {
		return new \WP_Error(
			'woocommerce_rest_cannot_edit',
			__( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return true;
}

/**
 * Check whether a given request has permission to delete customers.
 *
 * @param  \WP_REST_Request $request Full details about the request.
 * @return \WP_Error|boolean
 */
function permissions_check_delete( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	if ( ! wc_rest_check_user_permissions( 'delete' ) ) {
		return new \WP_Error(
			'woocommerce_rest_cannot_delete',
			__( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return true;
}
