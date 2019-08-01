/**
 * Handles logic for the WooCommerce Address Book plugin.
 *
 * @package woo-address-book
 */

(function($){

	 // SelectWoo / Select2 Enhancement if it exists.
	 if ($.fn.selectWoo) {
		 $( 'select#shipping_address:visible, select#address_book:visible' ).each(
			 function () {
				 $( this ).selectWoo();
			 }
		 );
	 } else if ($.fn.select2) {
		 $( 'select#shipping_address:visible, select#address_book:visible' ).each(
			 function () {
				 $( this ).select2();
			 }
		 );
	 }

	 /*
	 * AJAX call to delete address books.
	 */
	 $( '.address_book .wc-address-book-delete' ).click(
		 function (e) {

			 e.preventDefault();

			 $( this ).closest( '.wc-address-book-address' ).addClass( 'blockUI blockOverlay wc-updating' );

			 var name = $( this ).attr( 'id' );

			 $.ajax(
				 {
					 url: woo_address_book.ajax_url,
					 type: 'post',
					 data: {
						 action: 'wc_address_book_delete',
						 name: name
					 },
					 success: function (response) {
						 $( '.wc-updating' ).remove();
					 }
				 }
			 );
		 }
	 );

	 /*
	 * AJAX call to switch address to primary.
	 */
	 $( '.address_book .wc-address-book-make-primary' ).click(
		 function (e) {

			 e.preventDefault();

			 var name            = $( this ).attr( 'id' );
			 var primary_address = $( '.woocommerce-Addresses .u-column2.woocommerce-Address address' );
			 var alt_address     = $( this ).parent().siblings( 'address' );

			 // Swap HTML values for address and label.
			 var pa_html = primary_address.html();
			 var aa_html = alt_address.html();

			 alt_address.html( pa_html );
			 primary_address.html( aa_html );

			 primary_address.addClass( 'blockUI blockOverlay wc-updating' );
			 alt_address.addClass( 'blockUI blockOverlay wc-updating' );

			 $.ajax(
				 {
					 url: woo_address_book.ajax_url,
					 type: 'post',
					 data: {
						 action: 'wc_address_book_make_primary',
						 name: name
					 },
					 success: function (response) {
						 $( '.wc-updating' ).removeClass( 'blockUI blockOverlay wc-updating' );
					 }
				 }
			 );
		 }
	 );

	 /*
	 * AJAX call display address on checkout when selected.
	 */
	 function shipping_checkout_field_prepop() {

		 var that = $( '#address_book_field #address_book' );
		 var name = $( that ).val();

		 if (name !== undefined) {

			 if ('add_new' == name) {

				 // Clear values when adding a new address.
				 $( '.shipping_address input' ).not( $( '#shipping_country' ) ).each(
					 function () {
						 $( this ).val( '' );
					 }
				 );

				 // Set Country Dropdown.
				 // Don't reset the value if only one country is available to choose.
				 var country_input = $( '#shipping_country' );
				 if (country_input.length > 0 && country_input.attr("readonly") !== "readonly") {
					 country_input.val( '' ).change();
					 $( "#shipping_country_chosen" ).find( 'span' ).html( '' );
				 }

				 // Set state dropdown.
				 var state_input = $( '#shipping_state' );
				 if (state_input.length > 0 && state_input.attr("readonly") !== "readonly") {
					 state_input.val( '' ).change();
					 $( "#shipping_state_chosen" ).find( 'span' ).html( '' );
				 }

				 return;
			 }

			 if (name.length > 0) {

				 $( that ).closest( '.shipping_address' ).addClass( 'blockUI blockOverlay wc-updating' );

				 $.ajax(
					 {
						 url: woo_address_book.ajax_url,
						 type: 'post',
						 data: {
							 action: 'wc_address_book_checkout_update',
							 name: name
						 },
						 dataType: 'json',
						 success: function (response) {

							 // Loop through all fields incase there are custom ones.
							 Object.keys( response ).forEach(
								 function (key) {
									 var input = $( '#' + key );
									 if (input.length > 0 && input.attr("readonly") !== "readonly") {
										 input.val( response[key] ).change();
									 }
								 }
							 );

							 // Set Country Dropdown.
							 var country_input = $( '#shipping_country' );
							 if (country_input.length > 0 && country_input.attr("readonly") !== "readonly") {
								 country_input.val( response.shipping_country ).change();
								 $( "#shipping_country_chosen" ).find( 'span' ).html( response.shipping_country_text );
							 }

							 // Set state dropdown.
							 var state_input = $( '#shipping_state' );
							 if (state_input.length > 0 && state_input.attr("readonly") !== "readonly") {
								 state_input.val( response.shipping_state );
								 var stateName = $( '#shipping_state option[value="' + response.shipping_state + '"]' ).text();
								 $( "#s2id_shipping_state" ).find( '.select2-chosen' ).html( stateName ).parent().removeClass( 'select2-default' );
							 }

							 // Remove loading screen.
							 $( '.shipping_address' ).removeClass( 'blockUI blockOverlay wc-updating' );

						 }
					 }
				 );

			 }
		 }
	 }

	 shipping_checkout_field_prepop();

	 $( '#address_book_field #address_book' ).change(
		 function () {
			 shipping_checkout_field_prepop();
		 }
	 );

})(jQuery);
