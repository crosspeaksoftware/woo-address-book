=== WooCommerce Address Book ===
Contributors: hallme, doomwaxer, timbhowe, matt-h-1
Tags: WooCommerce, address book, multiple addresses, address
Requires at least: 4.6
Tested up to: 5.3.1
Stable tag: 1.7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

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

= 1.7.3 =
* Fix regression which caused an error when creating a new user during checkout.

= 1.7.2 =
* Fix regression from security update in 1.6.0 which broke saving new addresses on checkout.
* Code cleanup.

= 1.7.1 =
* Fixed if you had [selectize.js](https://selectize.github.io/selectize.js/) installed on the site but still using selectWoo for checkout. You must now manually apply selectize to the Address Book selector if you wish to use selectize on checkout.

= 1.7.0 =
* Fixed issue with selectWoo not loadingon checkout if "Ship to a different address?" was not enabled by default. Thanks [titodevera](https://github.com/titodevera) - [#62](https://github.com/hallme/woo-address-book/pull/62)
* Add support for [selectize.js](https://selectize.github.io/selectize.js/) if used over selectWoo for your select boxes.

= 1.6.1 =
* Remove unneeded nopriv ajax actions. [#60](https://github.com/hallme/woo-address-book/pull/58) [#61](https://github.com/hallme/woo-address-book/issues/61)

= 1.6.0 =
* Added support for address nicknames. Thanks [titodevera](https://github.com/titodevera) - [#60](https://github.com/hallme/woo-address-book/pull/58) [#59](https://github.com/hallme/woo-address-book/pull/60)
* Security: Updated all save calls to do nonce verification checks.
* Update endpoint url generation to prevent the query parameter from being filtered out. Fixes conflict with [WPML plugin](https://wordpress.org/support/topic/issue-with-woocommerce-multilingual-plugin/).

= 1.5.6 =
* Stop enqueuing the plugin styles and scripts on every page. Only enqueue them when needed. Thanks [titodevera](https://github.com/titodevera) - [#58](https://github.com/hallme/woo-address-book/pull/58) [#59](https://github.com/hallme/woo-address-book/pull/59)

= 1.5.5 =
* Fix changing country if the field is set to Read Only. Do not change any fields that are set to Read Only.

= 1.5.4 =
* Fix missing close div in address book form. Thanks [ThomasK0lasa](https://github.com/ThomasK0lasa) - [#54](https://github.com/hallme/woo-address-book/pull/54)
* Use a minified version of scripts.js
* Improve address saving for determining what address name to use next.
* Fix first address creation on checkout from being set to shipping2 [#55](https://github.com/hallme/woo-address-book/issues/55)
* Don't show address picker for new users that don't have addreses yet.

= 1.5.3 =
* Fix regression from 1.5.0 which broke switching primary address. Thanks [ThomasK0lasa](https://github.com/ThomasK0lasa) - [#53](https://github.com/hallme/woo-address-book/issues/53)

= 1.5.2 =
* Update country field with updates from WooCommerce 3.6.x. Fixes inconsistencies with updated stock version.
* Support SelectWoo in addition to select2 for checkout address book selector

= 1.5.1 =
* Update address book links so they don't have a trailing slash on them.

= 1.5.0 =
* Fix address saving for new addresses in the address book for WooCommerce 3.6.x due to a change in the save address process.
* Fix handling of empty address books. No longer populate address books of all users on activate since we handle empty books now.
* Fix issue saving more than 10 addresses to the addresse book. Thanks @JonBoss5

= 1.4.1 =
* Limit get_users to just returning IDs. Significantly decreases the amount of memory needed on activation on a site with many users. ([thanks pjv](https://github.com/hallme/woo-address-book/pull/40))
* PHP and JS formatting cleanup

= 1.4.0 =
* Trim any trailing slashes when getting the address name from the URL
* Do not display the address book dropdown on checkout if a default shipping address has not been set.
* Various bug fixes.

= 1.3.6 =
* Added `wc_address_book_addresses` filter to allow for modification of Address Book addresses.
* JS improvments of address book dropdown on Checkout. Thanks to ebelrose for their contributions!

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
