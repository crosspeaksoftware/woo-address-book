=== WooCommerce Address Book ===
Contributors: crosspeak, hallme, doomwaxer, timbhowe, matt-h-1, hinyka
Tags: WooCommerce, address book, multiple addresses, address
Requires at least: 4.6
Tested up to: 6.5.3
Stable tag: 2.6.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gives your customers the option to store multiple billing and shipping addresses and retrieve them on checkout.

== Description ==

**Notice** - This plugin is does not currently work with the WooCommerce Block Checkout. It currently only supports the Classic WooCommerce checkout. We hope to find a way to integrate with the Block Checkout in the future.

Gives your customers the option to store multiple billing and shipping addresses and retrieve them on checkout. Addresses can be updated and modified quickly and easily in /my-account/, or saved as part of the checkout process.

There is a [demo setup](https://woo-address-book.crosspeak.dev) if you would like to try this plugin out with a demo store.

= Settings =

The settings for this plugin are located in WooCommerce General settings: WooCommerce -> Settings -> General.

WooCommerce Address Book options:

* Enable billing/shipping address book
* Add New Address as default selection
* Enable setting Billing/Shipping Address Nickname during Checkout

= Code =

View the source on [GitHub](https://github.com/crosspeaksoftware/woo-address-book). You can also submit an [issue](https://github.com/crosspeaksoftware/woo-address-book/issues) or pull request for anything new.

== Installation ==

1. Upload the `woo-address-book` folder to the `/wp-content/plugins/` directory
2. Make sure you have WooCommerce installed and enabled.
3. Activate the WooCommerce Address Book through the 'Plugins' menu in WordPress.
4. Address Book options will now appear on the customer's account page and checkout once they've entered their primary billing or shipping address.

== Frequently Asked Questions ==

= Why can't I add a new address to my address book? =
The address book will only begin to display after the primary billing or shipping address has been created for the customer.

= Will this plugin allow my customers to ship to multiple locations with a single order? =
No, this plugin only allows for the storage of multiple shipping addresses. If a customer would like to ship to multiple locations, they should complete multiple orders.

= Why is the address not populating my custom fields at checkout? =
Most standard custom fields do work with the Address Book. However, if you have custom fields added by a plugin which are updated by javascript then the Address Book plugin will not always know how to handle the data. If you are running into an issue please post an issue in the [support forum](https://wordpress.org/support/plugin/woo-address-book/) or on [github](https://github.com/crosspeaksoftware/woo-address-book/issues) with what plugin or code you are using and as much details as you can. We will determine if the Address Book plugin is able to provide support for the plugin or if a custom solution would need to be developed for your use case.

= How can I add custom fields to my billing/shipping address? =
WooCommerce Address Book uses the standard WooCommerce address functions so any method to modify the address fields will still work.
We have tested that using the standard filters works correctly:
* [https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/](https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/)
We have also tested this plugin by WooCommerce:
* [https://woocommerce.com/products/woocommerce-checkout-field-editor/](https://woocommerce.com/products/woocommerce-checkout-field-editor/)

= How do I translate this plugin? =
We now use the new way of translating WordPress plugins at [https://translate.wordpress.org/](https://translate.wordpress.org/)

Feel free to contribute a translation at [https://translate.wordpress.org/projects/wp-plugins/woo-address-book/](https://translate.wordpress.org/projects/wp-plugins/woo-address-book/)

= How do I get my translation approved? =
Someone needs to apply to be a translation editor for this plugin. If you have contributed a translation, you may apply to be the editor yourself.

You can make the request and that request can be approved by the WordPress Localization editors.
See here for more details: [https://make.wordpress.org/polyglots/handbook/rosetta/roles-and-capabilities/pte-request/#pte-request-by-a-translator](https://make.wordpress.org/polyglots/handbook/rosetta/roles-and-capabilities/pte-request/#pte-request-by-a-translator)

= How can I use my translation before it is approved? =
What you can do to use it locally right now is go to the translate page.
At the bottom by the Export link, select "Only matching the filter" and "Machine Object Message Catalog (.mo)" and then click Export to download the `.mo` file.

Then save this .mo file to your WordPress site at `wp-content/languages/plugins/woo-address-book-LANGUAGE.mo` replacing `LANGUAGE` with your language code.

For example for German, you would go here:
[https://translate.wordpress.org/projects/wp-plugins/woo-address-book/stable/de/default/](https://translate.wordpress.org/projects/wp-plugins/woo-address-book/stable/de/default/)

Then save this .mo file to your WordPress site at `wp-content/languages/plugins/woo-address-book-de_DE.mo`

You may also use PoEdit and create a translation file which can be exported as a `.mo` file to be saved in the same location.

== Screenshots ==
1. Manage your address book on the account page. Choose your primary billing address, shipping address, or add multiple alternative addresses.
2. Easily select your billing and shipping address on checkout.

== Changelog ==

= 2.6.4 =
* Use WooCommerce ajax instead of WordPress ajax for improved compatibility.

= 2.6.3 =
* Declare checkout block incompatibility.
* Bump versions.

= 2.6.1 and 2.6.2 =
* Detect and downgrade from Address Book 3.0 addresses if they exist.

= 2.6.0 =
* Add notice about 3.0.0
* Warning: *3.x+* will be a breaking change if you have customizations for the plugin.

= 2.5.0 =
* Declare HPOS compatibility.
* Feature: Add option to use radio button instead of dropdown select.

= 2.4.1 =
* Tweak: Update change detection on Address Book selector so the listeners still work even if the elements are reloaded on the page.

= 2.4.0 =
* New: Setting to block readonly fields from updating when changing addresses. They will be updatable by default.

= 2.3.0 =
* Dev: **Rare potential breaking change if overriding javascript** Restructured javascript to be more modular and allow external access. [#133](https://github.com/crosspeaksoftware/woo-address-book/pull/133)
* New: New feature to set a limit for number of saved addresses in the admin settings.

= 2.2.0 =
* Update: When "Enable the shipping calculator on the cart page" is not checked in the admin then the City/State/Zip fields will not be pre-populated on Add New Address.

= 2.1.4 =
* Fix: When using WooCommerce Subscriptions, preserve existing address when doing a manual payment on the subscription.
* Cleanup: Improve the checking for the address from the cart to prevent some issues with it not being loaded.

= 2.1.3 =
* Fix: Address saving to customer Address Book if Billing or Shipping Address Book was disabled. [#128](https://github.com/crosspeaksoftware/woo-address-book/issues/128)

= 2.1.2 =
* Fix: "Enable setting Billing Address Nickname during Checkout" setting not working properly [#121](https://github.com/crosspeaksoftware/woo-address-book/issues/121)

= 2.1.1 =
* Require jquery-blockui for the script to fix loading order.

= 2.1.0 =
* Add `wc_address_book_show_billing_address_button` and `wc_address_book_show_shipping_address_button` so you can programmatically disable the add new address buttons.

= 2.0.2 and 2.0.3 =
* Improve compatibility with [Conditional Checkout Fields for WooCommerce](https://woocommerce.com/products/conditional-checkout-fields-for-woocommerce/)

= 2.0.1 =
* Fix select address issue when 'Add New Address as default selection' is enabled for shipping address.
* Improve backwards compatibility with versions prior to 2.0.0
* Better support custom field types other than standard input field.

= 2.0.0 =
* This is version 2.0.0 which is a major update with new functionality, be sure to review the changes below.
* Billing and Shipping address support. This release adds support for Billing addresses in addition to Shipping addresses.
* New settings to be able to Enable or Disable the address book for billing or shipping. Thanks [Hinyka](https://github.com/crosspeaksoftware/woo-address-book/pull/97)
  * Make sure to set these settings for your store after updating. Both are enabled by default.
* Adds settings to set if new address should be selected or primary address on checkout.
* Adds setting if the address nickname field should show on checkout.
* Fix checkbox to change address that didn't work from showing with WooCommerce Subscriptions plugin.
* Adds confirmation when deleting an address from the address book.
* Support for PHP 8 and jQuery 3. Thanks [Hinyka](https://github.com/crosspeaksoftware/woo-address-book/pull/96)
* Various other fixes, see [#97](https://github.com/crosspeaksoftware/woo-address-book/pull/97) for details.

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
* Fixed issue with selectWoo not loadingon checkout if "Ship to a different address?" was not enabled by default. Thanks [titodevera](https://github.com/titodevera) - [#62](https://github.com/crosspeaksoftware/woo-address-book/pull/62)
* Add support for [selectize.js](https://selectize.github.io/selectize.js/) if used over selectWoo for your select boxes.

= 1.6.1 =
* Remove unneeded nopriv ajax actions. [#60](https://github.com/crosspeaksoftware/woo-address-book/pull/58) [#61](https://github.com/crosspeaksoftware/woo-address-book/issues/61)

= 1.6.0 =
* Added support for address nicknames. Thanks [titodevera](https://github.com/titodevera) - [#60](https://github.com/crosspeaksoftware/woo-address-book/pull/58) [#59](https://github.com/crosspeaksoftware/woo-address-book/pull/60)
* Security: Updated all save calls to do nonce verification checks.
* Update endpoint url generation to prevent the query parameter from being filtered out. Fixes conflict with [WPML plugin](https://wordpress.org/support/topic/issue-with-woocommerce-multilingual-plugin/).

= 1.5.6 =
* Stop enqueuing the plugin styles and scripts on every page. Only enqueue them when needed. Thanks [titodevera](https://github.com/titodevera) - [#58](https://github.com/crosspeaksoftware/woo-address-book/pull/58) [#59](https://github.com/crosspeaksoftware/woo-address-book/pull/59)

= 1.5.5 =
* Fix changing country if the field is set to Read Only. Do not change any fields that are set to Read Only.

= 1.5.4 =
* Fix missing close div in address book form. Thanks [ThomasK0lasa](https://github.com/ThomasK0lasa) - [#54](https://github.com/crosspeaksoftware/woo-address-book/pull/54)
* Use a minified version of scripts.js
* Improve address saving for determining what address name to use next.
* Fix first address creation on checkout from being set to shipping2 [#55](https://github.com/crosspeaksoftware/woo-address-book/issues/55)
* Don't show address picker for new users that don't have addreses yet.

= 1.5.3 =
* Fix regression from 1.5.0 which broke switching primary address. Thanks [ThomasK0lasa](https://github.com/ThomasK0lasa) - [#53](https://github.com/crosspeaksoftware/woo-address-book/issues/53)

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
* Limit get_users to just returning IDs. Significantly decreases the amount of memory needed on activation on a site with many users. ([thanks pjv](https://github.com/crosspeaksoftware/woo-address-book/pull/40))
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
