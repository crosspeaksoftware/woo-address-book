=== WooCommerce Address Book ===
Contributors: hallme, doomwaxer, timbhowe, matt-h-1
Tags: WooCommerce, address book, multiple addresses, address
Requires at least: 4.6
Tested up to: 5.7.0
Stable tag: 1.7.5
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

= How can I add custom fields to my shipping address? =
WooCommerce Address Book uses the standard WooCommerce address functions so any method to modify the shipping fields will still work.
We have tested that using the standard filters work correctly:
* https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
We have also tested this plugin by WooCommerce:
* https://woocommerce.com/products/woocommerce-checkout-field-editor/

= How do I translate this plugin? =
We now use the new way of translating WordPress plugins at https://translate.wordpress.org/

Feel free to contribute a translation at https://translate.wordpress.org/projects/wp-plugins/woo-address-book/

= How do I get my translation approved? =
Someone needs to apply to be a translation editor for this plugin. If you have contributed a translation, you may apply to be the editor yourself.

You can make the request and that request can be approved by the WordPress Localization editors.
See here for more details: https://make.wordpress.org/polyglots/handbook/rosetta/roles-and-capabilities/pte-request/#pte-request-by-a-translator

= How can I use my translation before it is approved? =
What you can do to use it locally right now is go to the translate page.
At the bottom by the Export link, select "Only matching the filter" and "Machine Object Message Catalog (.mo)" and then click Export to download the `.mo` file.

Then save this .mo file to your WordPress site at `wp-content/languages/plugins/woo-address-book-LANGUAGE.mo` replacing `LANGUAGE` with your language code.

For example for German, you would go here:
https://translate.wordpress.org/projects/wp-plugins/woo-address-book/stable/de/default/

Then save this .mo file to your WordPress site at `wp-content/languages/plugins/woo-address-book-de_DE.mo`

You may also use PoEdit and create a translation file which can be exported as a `.mo` file to be saved in the same location.

= Where are the settings for this plugin? =

There are currently no settings, everything is based on your current WooCommerce address and checkout settings.

== Screenshots ==
1. Manage your address book on the account page. Choose your primary shipping address, or add multiple alternative addresses.
2. Easily select your shipping address on checkout.

== Changelog ==

= 1.7.5 =
* Update Country strings that were changed in WooCommerce 4.0
* add `load_plugin_textdomain`

= 1.7.4 =
* Update to support the latest versions of WooCommerce 3.x and 4.0.0 with Customer CRUD functions.

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
