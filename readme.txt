=== WooCommerce Address Book ===
Contributors: hallme, doomwaxer, timbhowe
Tags: WooCommerce, address book, multiple addresses, address
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gives your customers the option to store multiple shipping addresses and retrieve them on checkout.

== Description ==

Gives your customers the option to store multiple shipping addresses and retrieve them on checkout. Addresses can be updated and modified quickly and easily in /my-account/, or saved as part of the checkout process.

= Code =

View the source on [GitHub](https://github.com/hallme/woo-address-book). You can also submit an [issue](https://github.com/hallme/woo-address-book/issues) or pull request for anything new.

== Installation ==

1. Upload the `woo-address-book` folder to the `/wp-content/plugins/` directory
2. Make sure you have WooCommerce installed and enabled.
3. Activate the WooCommerce Address Book through the 'Plugins' menu in WordPress.
4. Address Book options will now appear on the customer's account page and checkout once they've entered their primary shipping address.

== Frequently Asked Questions ==

= Why can't I add a new address to my address book? =
The address book will only begin to display after the primary shipping address has been created for the customer.

= Will this plugin allow my customers to ship to multiple locations with a single order? =
No, this plugin only allows for the storage of multiple shipping addresses. If a customer would like to ship to multiple locations, they should complete multiple orders.

== Screenshots ==
1. Manage your address book on the account page. Choose your primary shipping address, or add multiple alternative addresses.
2. Easily select your shipping address on checkout.

== Changelog ==

= 1.3.5 =
* Added languages folder with .pot file for i18n.
* Added `isset()` conditions for `address_select_label()` in case fields have been removed.

= 1.3.4 =
* Updated the filter wc_address_book_address_select_label to add $address and $name, which should open a lot of options for modifying the output.
* Cleaned up the codebase to align with [WordPress-Coding-Standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards).

= 1.3.3 =
* Added text domain and updated AJAX's scope on my-account. Thanks to pabl0rg and nicolasmn for their contributions!

= 1.3.2 =
* Fixed a bug to properly reset the state value when selecting 'new address'.

= 1.3.1 =
* Fixed a bug which prevents shipping country field from clearing if only one country is a checkout option.

= 1.3.0 =
* Add multisite support.

= 1.2.1 =
* Fixed a bug which prevented address from saving.

= 1.2 =
* Clear checkout fields when adding a new address.

= 1.1 =
* Changed Address Book to using $_GET vars to allow for custom fields to be saved.

= 1.0 =
* Initial Release.
