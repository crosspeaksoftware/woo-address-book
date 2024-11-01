/**
 * Handles logic for the WooCommerce Address Book plugin.
 *
 * @package woo-address-book
 */

var woo_address_book_app = {

	shipping_address_from_cart: '',

	init: function($) {
		this.jQuery = $;
		this.checkout();
	},

	checkout: function() {
		var $ = this.jQuery;
		var load_selectWoo = true;
		var address_book = $( 'select#shipping_address_book:visible, select#billing_address_book:visible' );
		if ( ! address_book.length ) {
			address_book = $( 'input[name="shipping_address_book"]:checked, input[name="billing_address_book"]:checked' );
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

		// BlockUI settings
		$.blockUI.defaults.message = null;
		$.blockUI.defaults.overlayCSS.backgroundColor = '#fff';

		// Retrieves default billing address
		woo_address_book_app.checkout_field_prepop( 'billing', true );

		// Retrieves billing address when another is selected.
		$( document ).on( 'change', '#billing_address_book_field #billing_address_book, #billing_address_book_field input[name="billing_address_book"]:checked', function () {
			woo_address_book_app.checkout_field_prepop( 'billing', false );
		} );

		this.shipping_address_from_cart = false;
		// Customer entered address into the shipping calculator
		if ( $("#woocommerce_enable_shipping_calc").val() === "yes" && $( "form[name=checkout]" ).length > 0 && ( $( "#shipping_country" ).val() !== "" || $( "#shipping_state" ).val() !== "" || $( "#shipping_city" ).val() !== "" || $( "#shipping_postcode" ).val() !== "" ) ) {
			shipping_country_o = $( "#shipping_country" ).val();
			shipping_state_o = $( "#shipping_state" ).val();
			shipping_city_o = $( "#shipping_city" ).val();
			shipping_postcode_o = $( "#shipping_postcode" ).val();
			this.shipping_address_from_cart = true;
		}

		// Retrieves default shipping address
		woo_address_book_app.checkout_field_prepop( 'shipping', true );

		// Retrieves shipping address when another is selected.
		$( document ).on( 'change', '#shipping_address_book_field #shipping_address_book, #shipping_address_book_field input[name="shipping_address_book"]:checked', function () {
			woo_address_book_app.checkout_field_prepop( 'shipping', false );
		} );

		// Update checkout when address changes
		if ( $( "form[name=checkout]" ).length > 0 ) {
			$( '#shipping_country, #shipping_state_field, #shipping_city, #shipping_postcode, #billing_country, #billing_state_field, #billing_city, #billing_postcode' ).on( 'change', function () {
				$( document.body ).trigger( 'update_checkout' );
			} );
		};

		/*
		 * AJAX call to delete address books.
		 */
		$( 'a.wc-address-book-delete' ).on( 'click', function ( e ) {

			e.preventDefault();

			var confirmDelete = confirm( woo_address_book.delete_confirmation );
			if ( ! confirmDelete ) {
				return;
			}

			var addressBook = $( this ).closest( '.address_book' );
			var name = $( this ).attr( 'id' );
			var toRemove = $( this ).closest( '.wc-address-book-address' );
			var numOfAddresses = parseInt( addressBook.attr( 'data-addresses' ) );
			var addressLimit = parseInt( addressBook.attr( 'data-limit' ) );

			// Show BlockUI overlay
			$( '.woocommerce-MyAccount-content' ).block();

			$.ajax( {
				url: woo_address_book.wc_ajax_url.toString()
					.replace( '%%endpoint%%', 'wc_address_book_delete' ),
				type: 'post',
				data: {
					name: name,
					nonce: woo_address_book.delete_security,
				},
				success: function () {
					toRemove.remove();
					addressBook.attr( 'data-addresses', numOfAddresses - 1 );
					if ( numOfAddresses <= addressLimit ) {
						addressBook.find(".wc-address-book-add-new-address span").hide();
						addressBook.find(".wc-address-book-add-new-address a").show();
					}

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
				url: woo_address_book.wc_ajax_url.toString()
					.replace( '%%endpoint%%', 'wc_address_book_make_primary' ),
				type: 'post',
				data: {
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

	},

	/*
	 * AJAX call display address on checkout when selected.
	 */
	checkout_field_prepop: function( address_type, initial_address ) {
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
				$( '.woocommerce-' + address_type + '-fields__field-wrapper input' ).not( $( '#' + address_type + '_country' ) ).not( '#' + address_type + '_address_book' ).not( '[name="' + address_type + '_address_book"]' ).each( function () {
					var input = $( this );
					if ( input.attr("type") === "checkbox" || input.attr("type") === "radio") {
						input.prop( "checked", false );
					} else {
						input.val( '' );
					}
				} );
				$( '.woocommerce-' + address_type + '-fields__field-wrapper select' ).not( $( '#' + address_type + '_country' ) ).not( '#' + address_type + '_address_book' ).each( function () {
					var input = $( this );
					if ( input.hasClass( 'selectized' ) && input[0] && input[0].selectize ) {
						input[0].selectize.setValue( "" );
					} else {
						input.val( "" ).trigger( 'change' );
					}
				} );

				// If shipping calculator used on cart page
				if ( address_type === 'shipping' && this.shipping_address_from_cart ) {

					$( "#shipping_country" ).val( shipping_country_o ).trigger( 'change' );
					$( "#shipping_state" ).val( shipping_state_o ).trigger( 'change' );
					$( "#shipping_city" ).val( shipping_city_o );
					$( "#shipping_postcode" ).val( shipping_postcode_o );

					this.shipping_address_from_cart = false;

					// Remove BlockUI overlay
					$( '.woocommerce-shipping-fields' ).unblock();

				}

				return;
			}

			if ( name.length > 0 ) {

				// Show BlockUI overlay
				$( '.woocommerce-' + address_type + '-fields' ).block();

				$.ajax( {
					url: woo_address_book.wc_ajax_url.toString()
						.replace( '%%endpoint%%', 'wc_address_book_checkout_update' ),
					type: 'post',
					data: {
						name: name,
						type: address_type,
						nonce: woo_address_book.checkout_security,
					},
					dataType: 'json',
					success: function ( response ) {

						// If shipping calculator used on cart page
						if ( initial_address && address_type === 'shipping' && this.shipping_address_from_cart ) {
							// If entered values do not equal shipping calculator values
							if ( shipping_country_o !== response.shipping_country || shipping_state_o !== response.shipping_state || shipping_city_o !== response.shipping_city || shipping_postcode_o !== response.shipping_postcode ) {
								$( "#shipping_address_book" ).val( 'add_new' ).trigger( 'change' );
								$( 'input#shipping_address_book_add_new').prop( 'checked', true ).trigger( 'change' );
								return;
							}
						}

						// Loop through all fields and set values to the form.
						Object.keys( response ).forEach( function ( key ) {
							let input = $( '#' + key );
							if ( input.length > 0 ) {
								if ( woo_address_book.allow_readonly !== "no" || input.attr( 'readonly' ) !== 'readonly' ) {
									if ( input.is("select") ) {
										if ( input.hasClass( 'selectized' ) && input[0] && input[0].selectize ) {
											input[0].selectize.setValue( response[key] );
										} else {
											input.val( response[key] ).trigger( 'change' );
										}
									} else if ( input.attr("type") === "checkbox" ) {
										input.prop( 'checked', response[key] === "1" ).trigger( 'change' );
									} else {
										input.val( response[key] ).trigger( 'change' );
									}
								}
							} else {
								// Handle radio buttons.
								let radio_field = $( '#' + key + '_field' );
								if ( radio_field.length > 0 ) {
									radio_field.find("input[type='radio']").each( function (index, radio_button) {
										if ( $(radio_button).val() === response[key] ) {
											$(radio_button).prop( 'checked', true ).trigger( 'change' );
										}
									});
								}
							}
						} );

						// Remove BlockUI overlay
						$( '.woocommerce-' + address_type + '-fields' ).unblock();
					}
				} );
			}
		}
	}

};

jQuery( function( $ ) {

	'use strict';

	woo_address_book_app.init($);

} );
