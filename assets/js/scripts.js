/**
 * Handles logic for the WooCommerce Address Book plugin.
 *
 * @package woo-address-book
 */

var woo_address_book_app = {

	shipping_address_from_cart: '',

	init: function ($) {
		this.jQuery = $;
		this.checkout();
	},

	checkout: function () {
		var $              = this.jQuery;
		var load_selectWoo = true;
		var address_book   = $( 'select#shipping_address_book:visible, select#billing_address_book:visible' );
		if ( ! address_book.length ) {
			address_book   = $( 'input[name="shipping_address_book"]:checked, input[name="billing_address_book"]:checked' );
			load_selectWoo = false;
		}

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

		// BlockUI settings.
		$.blockUI.defaults.message                    = null;
		$.blockUI.defaults.overlayCSS.backgroundColor = '#fff';

		// Retrieves default billing address.
		woo_address_book_app.checkout_field_prepop( 'billing', true );

		// Retrieves billing address when another is selected.
		$( document ).on(
			'change',
			'#billing_address_book_field #billing_address_book, #billing_address_book_field input[name="billing_address_book"]:checked',
			function () {
				woo_address_book_app.checkout_field_prepop( 'billing', false );
			}
		);

		this.shipping_address_from_cart = false;
		// Customer entered address into the shipping calculator.
		if ( $( "#woocommerce_enable_shipping_calc" ).val() === "yes" && $( "form[name=checkout]" ).length > 0 && ( $( "#shipping_country" ).val() !== "" || $( "#shipping_state" ).val() !== "" || $( "#shipping_city" ).val() !== "" || $( "#shipping_postcode" ).val() !== "" ) ) {
			shipping_country_o              = $( "#shipping_country" ).val();
			shipping_state_o                = $( "#shipping_state" ).val();
			shipping_city_o                 = $( "#shipping_city" ).val();
			shipping_postcode_o             = $( "#shipping_postcode" ).val();
			this.shipping_address_from_cart = true;
		}

		// Retrieves default shipping address.
		woo_address_book_app.checkout_field_prepop( 'shipping', true );

		// Retrieves shipping address when another is selected.
		$( document ).on(
			'change',
			'#shipping_address_book_field #shipping_address_book, #shipping_address_book_field input[name="shipping_address_book"]:checked',
			function () {
				woo_address_book_app.checkout_field_prepop( 'shipping', false );
			}
		);

		// Update checkout when address changes.
		if ( $( "form[name=checkout]" ).length > 0 ) {
			$( '#shipping_country, #shipping_state_field, #shipping_city, #shipping_postcode, #billing_country, #billing_state_field, #billing_city, #billing_postcode' ).on(
				'change',
				function () {
					$( document.body ).trigger( 'update_checkout' );
				}
			);
		};

		/*
		 * AJAX call to delete address books.
		 */
		$( '.wc-address-book-delete' ).on(
			'click',
			function () {

				var confirmDelete = confirm( woo_address_book.delete_confirmation );
				if ( ! confirmDelete ) {
					return;
				}

				var addressBook    = $( this ).closest( '.address_book' );
				var name           = $( this ).data( 'wc-address-name' );
				var type           = $( this ).data( 'wc-address-type' );
				var toRemove       = $( this ).closest( '.wc-address-book-address' );
				var numOfAddresses = parseInt( addressBook.attr( 'data-addresses' ) );
				var addressLimit   = parseInt( addressBook.attr( 'data-limit' ) );

				// Show BlockUI overlay.
				$( '.woocommerce-MyAccount-content' ).block();

				$.ajax(
					{
						url: woo_address_book.ajax_url,
						type: 'post',
						data: {
							action: 'wc_address_book_delete',
							name: name,
							type: type,
							nonce: woo_address_book.delete_security,
						},
						success: function (response) {
							toRemove.remove();
							addressBook.attr( 'data-addresses', numOfAddresses - 1 );
							if ( numOfAddresses <= addressLimit ) {
								addressBook.find( ".wc-address-book-add-new-address span" ).hide();
								addressBook.find( ".wc-address-book-add-new-address a" ).show();
							}

							$( '.woocommerce-notices-wrapper' ).html( response );

							// Remove BlockUI overlay.
							$( '.woocommerce-MyAccount-content' ).unblock();
						}
					}
				);
			}
		);

		$( '#wc_address_book_upload_billing_csv' ).on(
			'change',
			function ( e ) {
				if ( $( '#wc_address_book_upload_billing_csv' ).val() ) {
					$( '.billing-import-btn' ).show();
				}
			}
		);
		$( '#wc_address_book_upload_shipping_csv' ).on(
			'change',
			function ( e ) {
				if ( $( '#wc_address_book_upload_shipping_csv' ).val() ) {
					$( '.shipping-import-btn' ).show();
				}
			}
		);

		/*
		 * AJAX call to switch address to default.
		 */
		$( '.wc-address-book-make-default' ).on(
			'click',
			function () {

				var name = $( this ).data( 'wc-address-name' );
				if ( name === undefined || name === null || name === '' ) {
					return;
				}
				var type = $( this ).data( 'wc-address-type' );

				if ( type === 'billing' ) {
					var main_address_slot = $( '.u-column1.woocommerce-Address address' );
					var default_address   = $( '.billing_address_book .wc-address-book-address-default' );
				} else if ( type === 'shipping' ) {
					var main_address_slot = $( '.u-column2.woocommerce-Address address' );
					var default_address   = $( '.shipping_address_book .wc-address-book-address-default' );
				} else {
					return;
				}

				var new_address = $( this ).parents( ".wc-address-book-address" );

				// Show BlockUI overlay.
				$( '.woocommerce-MyAccount-content' ).block();

				$.ajax(
					{
						url: woo_address_book.ajax_url,
						type: 'post',
						data: {
							action: 'wc_address_book_make_default',
							name: name,
							type: type,
							nonce: woo_address_book.default_security,
						},
						success: function (response) {
							main_address_slot.html( new_address.find( "address" ).html() );
							new_address.addClass( 'wc-address-book-address-default' );
							new_address.find( '.wc-address-book-make-default' ).prop( 'disabled', true );
							new_address.find( '.wc-address-book-delete' ).prop( 'disabled', true );
							default_address.removeClass( 'wc-address-book-address-default' );
							default_address.find( '.wc-address-book-make-default' ).prop( 'disabled', false );
							default_address.find( '.wc-address-book-delete' ).prop( 'disabled', false );
							default_address.find( '.wc-address-book-address-badges' ).prependTo( new_address );
							$( '.woocommerce-notices-wrapper' ).html( response );

							// Remove BlockUI overlay.
							$( '.woocommerce-MyAccount-content' ).unblock();
						}
					}
				);
			}
		);

		$( '.wc-address-book-address-default .wc-address-book-meta button' ).prop( 'disabled', true );

	},

	/*
	 * AJAX call display address on checkout when selected.
	 */
	checkout_field_prepop: function ( address_type, initial_address ) {
		var $ = this.jQuery;

		if ( initial_address && $( '#' + address_type + '_address_book_field' ).hasClass( 'wc-address-book-subscription-renewal' ) ) {
			return;
		}
		let that = $( '#' + address_type + '_address_book_field #' + address_type + '_address_book' );
		if ( ! that.length ) {
			that = $( '#' + address_type + '_address_book_field input[name="' + address_type + '_address_book"]:checked' );
		}

		let name = $( that ).val();

		if ( name !== undefined && name !== null ) {

			if ( 'add_new' === name ) {

				// Clear values when adding a new address.
				$( '.woocommerce-' + address_type + '-fields__field-wrapper input' ).not( $( '#' + address_type + '_country' ) ).not( '#' + address_type + '_address_book' ).not( '[name="' + address_type + '_address_book"]' ).each(
					function () {
						var input = $( this );
						if ( input.attr( "type" ) === "checkbox" || input.attr( "type" ) === "radio") {
								input.prop( "checked", false );
						} else {
							input.val( '' );
						}
					}
				);
				$( '.woocommerce-' + address_type + '-fields__field-wrapper select' ).not( $( '#' + address_type + '_country' ) ).not( '#' + address_type + '_address_book' ).each(
					function () {
						var input = $( this );
						if ( input.hasClass( 'selectized' ) && input[0] && input[0].selectize ) {
								input[0].selectize.setValue( "" );
						} else {
							input.val( "" ).trigger( 'change' );
						}
					}
				);

				// If shipping calculator used on cart page.
				if ( address_type === 'shipping' && this.shipping_address_from_cart ) {

					$( "#shipping_country" ).val( shipping_country_o ).trigger( 'change' );
					$( "#shipping_state" ).val( shipping_state_o ).trigger( 'change' );
					$( "#shipping_city" ).val( shipping_city_o );
					$( "#shipping_postcode" ).val( shipping_postcode_o );

					this.shipping_address_from_cart = false;

					// Remove BlockUI overlay.
					$( '.woocommerce-shipping-fields' ).unblock();

				}

				return;
			}

			if ( name.length > 0 ) {

				// Show BlockUI overlay.
				$( '.woocommerce-' + address_type + '-fields' ).block();

				$.ajax(
					{
						url: woo_address_book.ajax_url,
						type: 'post',
						data: {
							action: 'wc_address_book_checkout_update',
							name:     name,
							type: address_type,
							nonce: woo_address_book.checkout_security,
						},
						dataType: 'json',
						success: function ( response ) {

							// If shipping calculator used on cart page.
							if ( initial_address && address_type === 'shipping' && this.shipping_address_from_cart ) {
								// If entered values do not equal shipping calculator values.
								if ( shipping_country_o !== response.shipping_country || shipping_state_o !== response.shipping_state || shipping_city_o !== response.shipping_city || shipping_postcode_o !== response.shipping_postcode ) {
									$( "#shipping_address_book" ).val( 'add_new' ).trigger( 'change' );
									$( 'input#shipping_address_book_add_new' ).prop( 'checked', true ).trigger( 'change' );
									return;
								}
							}

							inputs_array = [];
							values_array = [];

							// Loop through all fields and set values to the form.
							Object.keys( response ).forEach(
								function ( key ) {
									let input = $( '#' + key );

									// Save set input values to variables.
									// Used to verify everything was set correctly after the fact.
									// Some localized plugins use a different order and can cause previous values to be removed.
									if ( typeof response[key] !== 'undefined' && typeof input.attr( "name" ) !== 'undefined' ) {
											inputs_array.push( input );
											values_array.push( response[key] );
									}

									if ( input.length > 0 ) {
										if ( woo_address_book.allow_readonly !== "no" || input.attr( 'readonly' ) !== 'readonly' ) {
											if ( input.is( "select" ) ) {
												if ( input.hasClass( 'selectized' ) && input[0] && input[0].selectize ) {
													input[0].selectize.setValue( response[key] );
												} else {
													input.val( response[key] ).trigger( 'change' );
												}
											} else if ( input.attr( "type" ) === "checkbox" ) {
												input.prop( 'checked', response[key] === "1" ).trigger( 'change' );
											} else {
												input.val( response[key] ).trigger( 'change' );
											}
										}
									} else {
										// Handle radio buttons.
										let radio_field = $( '#' + key + '_field' );
										if ( radio_field.length > 0 ) {
											radio_field.find( "input[type='radio']" ).each(
												function (index, radio_button) {
													if ( $( radio_button ).val() === response[key] ) {
															$( radio_button ).prop( 'checked', true ).trigger( 'change' );
													}
												}
											);
										}
									}
								}
							);

							// Loop through the inputs and make sure their values match the saved values.
							inputs_array.forEach(
								function ( input, index ) {
									if ( input.val() !== values_array[index] ) {
											input.val( values_array[index] ).trigger( 'change' );
									}
								}
							);

							// Remove BlockUI overlay.
							$( '.woocommerce-' + address_type + '-fields' ).unblock();
						}
					}
				);
			}
		}
	}

};

jQuery(
	function ( $ ) {

		'use strict';

		woo_address_book_app.init( $ );

	}
);
