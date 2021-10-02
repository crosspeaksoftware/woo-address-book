/**
 * Handles logic for the WooCommerce Address Book plugin.
 *
 * @package woo-address-book
 */

jQuery( function ( $ ) {

	var load_selectWoo = true;
	var address_book = $( 'select#shipping_address_book:visible, select#billing_address_book:visible' );

	// Check for Selectize being used.
	if ( $.fn.selectize ) {
		if ( address_book.hasClass( "selectized" ) && address_book[0] && address_book[0].selectize ) {
			load_selectWoo = false;
		}
	}

	// SelectWoo / Select2 Enhancement if it exists.
	if ( load_selectWoo ) {
		if ( $.fn.selectWoo ) {
			address_book.selectWoo();
		} else if ( $.fn.select2 ) {
			address_book.select2();
		}
	}

	// BlockUI settings
	$.blockUI.defaults.message = null;
	$.blockUI.defaults.overlayCSS.backgroundColor = '#fff';

	// Retrieves default billing address
	billing_checkout_field_prepop();

	// Retrieves billing address when another is selected.
	$( '#billing_address_book_field #billing_address_book' ).on( 'change', function () {
		billing_checkout_field_prepop();
	} );

	// Customer entered address into the shipping calculator
	if ( $( "form[name=checkout]" ).length > 0 && ( $( "#shipping_country" ).val() !== "" ||  $( "#shipping_state" ).val() !== "" || $( "#shipping_city" ).val() !== "" || $( "#shipping_postcode" ).val() !== "" ) ) {
		shipping_country_o = $( "#shipping_country" ).val();
		shipping_state_o = $( "#shipping_state" ).val();
		shipping_city_o = $( "#shipping_city" ).val();
		shipping_postcode_o = $( "#shipping_postcode" ).val();
	}

	// Retrieves default shipping address
	shipping_checkout_field_prepop();

	// Retrieves shipping address when another is selected.
	$( '#shipping_address_book_field #shipping_address_book' ).on( 'change', function () {
		shipping_checkout_field_prepop();
	} );

	// Update checkout when address changes
	if ( $( "form[name=checkout]" ).length > 0 ) {
		$( '#shipping_country, #shipping_state, #shipping_city, #shipping_postcode' ).on( 'change', function () {
			$( document.body ).trigger( 'update_checkout' );
		} );
	};

	/*
	 * AJAX call to delete address books.
	 */
	$( 'a.wc-address-book-delete' ).on( 'click', function ( e ) {

		e.preventDefault();

		var name = $( this ).attr( 'id' );
		var toRemove = $( this ).closest( '.wc-address-book-address' );

		// Show BlockUI overlay
		$( '.woocommerce-MyAccount-content' ).block();

		$.ajax( {
			url: woo_address_book.ajax_url,
			type: 'post',
			data: {
				action: 'wc_address_book_delete',
				name: name,
				nonce: woo_address_book.delete_security,
			},
			success: function () {
				toRemove.remove();

				// Remove BlockUI overlay
				$( '.woocommerce-MyAccount-content' ).unblock();
			}
		} );
	} );

	/*
	 * AJAX call to switch address to primary.
	 */
	$( 'a.wc-address-book-make-primary' ).on( 'click', function ( e ) {

		e.preventDefault();

		var name = $( this ).attr( 'id' );
		var type = name.replace( /\d+/g, '' );

		if ( type === 'billing' ) {
			var primary_address = $( '.u-column1.woocommerce-Address address' );
		} else if ( type === 'shipping' ) {
			var primary_address = $( '.u-column2.woocommerce-Address address' );
		} else {
			return;
		}

		var alt_address = $( this ).parent().siblings( 'address' );

		// Swap HTML values for address and label.
		var pa_html = primary_address.html();
		var aa_html = alt_address.html();

		// Show BlockUI overlay
		$( '.woocommerce-MyAccount-content' ).block();

		$.ajax( {
			url: woo_address_book.ajax_url,
			type: 'post',
			data: {
				action: 'wc_address_book_make_primary',
				name: name,
				nonce: woo_address_book.primary_security,
			},
			success: function () {
				alt_address.html( pa_html );
				primary_address.html( aa_html );
				
				// Remove BlockUI overlay
				$( '.woocommerce-MyAccount-content' ).unblock();
			}
		} );
	} );

	/*
	 * AJAX call display address on checkout when selected.
	 */
	function shipping_checkout_field_prepop() {

		var that = $( '#shipping_address_book_field #shipping_address_book' );
		var name = $( that ).val();

		if ( name !== undefined ) {

			if ( 'add_new' === name ) {

				// Clear values when adding a new address.
				$( '.shipping_address input' ).not( $( '#shipping_country' ) ).each( function () {
					$( this ).val( '' );
				} );

				// Set Country Dropdown.
				// Don't reset the value if only one country is available to choose.
				var country_input = $( '#shipping_country' );
				if ( country_input.length > 0 && country_input.attr( "readonly" ) !== "readonly" ) {
					country_input.val( '' ).trigger( 'change' );
					$( "#shipping_country_chosen" ).find( 'span' ).html( '' );
				}

				// Set state dropdown.
				var state_input = $( '#shipping_state' );
				if ( state_input.length > 0 && state_input.attr( "readonly" ) !== "readonly" ) {
					state_input.val( '' ).trigger( 'change' );
					$( "#shipping_state_chosen" ).find( 'span' ).html( '' );
				}

				return;
			}

			if ( name.length > 0 ) {

				// Show BlockUI overlay
				$( '.woocommerce-shipping-fields' ).block();

				$.ajax( {
					url: woo_address_book.ajax_url,
					type: 'post',
					data: {
						action: 'wc_address_book_checkout_update',
						name: name,
						type: 'shipping',
						nonce: woo_address_book.checkout_security,
					},
					dataType: 'json',
					success: function ( response ) {
						if ( typeof shipping_country_o !== 'undefined' && typeof shipping_state_o !== 'undefined' && typeof shipping_city_o !== 'undefined' && typeof shipping_postcode_o !== 'undefined' ) {

							if ( shipping_country_o !== response.shipping_country || shipping_state_o !== response.shipping_state || shipping_city_o !== response.shipping_city || shipping_postcode_o !== response.shipping_postcode ) {

								$( "#shipping_address_book" ).val( 'add_new' ).trigger( 'change' );

								$( "#shipping_country" ).val( shipping_country_o ).trigger( 'change' );
								$( "#shipping_state" ).val( shipping_state_o ).trigger( 'change' );
								$( "#shipping_city" ).val( shipping_city_o );
								$( "#shipping_postcode" ).val( shipping_postcode_o );
							}

							delete shipping_country_o;
							delete shipping_state_o;
							delete shipping_city_o;
							delete shipping_postcode_o;
							
							// Remove BlockUI overlay
							$( '.woocommerce-shipping-fields' ).unblock();

							return;
						}

						// Loop through all fields incase there are custom ones.
						Object.keys( response ).forEach( function ( key ) {
							var input = $( '#' + key );
							if ( input.length > 0 && input.attr( "readonly" ) !== "readonly" ) {
								input.val( response[key] ).trigger( 'change' );
							}
						} );

						// Set Country Dropdown.
						var country_input = $( '#shipping_country' );
						if ( country_input.length > 0 && country_input.attr( "readonly" ) !== "readonly" ) {
							if ( country_input.hasClass( "selectized" ) && country_input[0] && country_input[0].selectize ) {
								country_input[0].selectize.setValue( response.shipping_country );
							} else {
								country_input.val( response.shipping_country ).trigger( 'change' );
								$( "#shipping_country_chosen" ).find( 'span' ).html( response.shipping_country_text );
							}
						}

						// Set state dropdown.
						var state_input = $( '#shipping_state' );
						if ( state_input.length > 0 && state_input.attr( "readonly" ) !== "readonly" ) {
							if ( state_input.hasClass( "selectized" ) && state_input[0] && state_input[0].selectize ) {
								state_input[0].selectize.setValue( response.shipping_state );
							} else {
								state_input.val( response.shipping_state ).trigger( 'change' );
								var stateName = $( '#shipping_state option[value="' + response.shipping_state + '"]' ).text();
								$( "#s2id_shipping_state" ).find( '.select2-chosen' ).html( stateName ).parent().removeClass( 'select2-default' );
							}
						}

						// Remove BlockUI overlay
						$( '.woocommerce-shipping-fields' ).unblock();
					}
				} );
			}
		}
	}

	/*
	 * AJAX call display address on checkout when selected.
	 */
	function billing_checkout_field_prepop() {

		var that = $( '#billing_address_book_field #billing_address_book' );
		var name = $( that ).val();

		if ( name !== undefined ) {

			if ( 'add_new' === name ) {

				// Clear values when adding a new address.
				$( '.woocommerce-billing-fields__field-wrapper input' ).not( $( '#billing_country' ) ).each( function () {
					$( this ).val( '' );
				} );

				// Set Country Dropdown.
				// Don't reset the value if only one country is available to choose.
				var country_input = $( '#billing_country' );
				if ( country_input.length > 0 && country_input.attr( "readonly" ) !== "readonly" ) {
					country_input.val( '' ).trigger( 'change' );
					$( "#billing_country_chosen" ).find( 'span' ).html( '' );
				}

				// Set state dropdown.
				var state_input = $( '#billing_state' );
				if ( state_input.length > 0 && state_input.attr( "readonly" ) !== "readonly" ) {
					state_input.val( '' ).trigger( 'change' );
					$( "#billing_state_chosen" ).find( 'span' ).html( '' );
				}

				return;
			}

			if ( name.length > 0 ) {

				// Show BlockUI overlay
				$( '.woocommerce-billing-fields' ).block();

				$.ajax( {
					url: woo_address_book.ajax_url,
					type: 'post',
					data: {
						action: 'wc_address_book_checkout_update',
						name: name,
						type: 'billing',
						nonce: woo_address_book.checkout_security,
					},
					dataType: 'json',
					success: function ( response ) {

						// Loop through all fields incase there are custom ones.
						Object.keys( response ).forEach( function ( key ) {
							var input = $( '#' + key );
							if ( input.length > 0 && input.attr( "readonly" ) !== "readonly" ) {
								input.val( response[key] ).trigger( 'change' );
							}
						} );

						// Set Country Dropdown.
						var country_input = $( '#billing_country' );
						if ( country_input.length > 0 && country_input.attr( "readonly" ) !== "readonly" ) {
							if ( country_input.hasClass( "selectized" ) && country_input[0] && country_input[0].selectize ) {
								country_input[0].selectize.setValue( response.billing_country );
							} else {
								country_input.val( response.billing_country ).trigger( 'change' );
								$( "#billing_country_chosen" ).find( 'span' ).html( response.billing_country_text );
							}
						}

						// Set state dropdown.
						var state_input = $( '#billing_state' );
						if ( state_input.length > 0 && state_input.attr( "readonly" ) !== "readonly" ) {
							if ( state_input.hasClass( "selectized" ) && state_input[0] && state_input[0].selectize ) {
								state_input[0].selectize.setValue( response.billing_state );
							} else {
								state_input.val( response.billing_state ).trigger( 'change' );
								var stateName = $( '#billing_state option[value="' + response.billing_state + '"]' ).text();
								$( "#s2id_billing_state" ).find( '.select2-chosen' ).html( stateName ).parent().removeClass( 'select2-default' );
							}
						}

						// Remove BlockUI overlay
						$( '.woocommerce-billing-fields' ).unblock();
					}
				} );
			}
		}
	}

} );
