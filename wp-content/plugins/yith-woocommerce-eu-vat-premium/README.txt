=== YITH WooCommerce EU VAT Premium ===

Contributors: yithemes
Tags: digital VAT, digital goods, EU tax, EU VAT, euvat, IVA, moss, EU moss, european tax, VAT compliance, EU VAT compliance, woocommerce, e-commerce, e commerce, tax, order tax, order VAT, EU VAT compliance, european VAT, VAT moss, VAT rates, vatmoss, taxes, VAT, vatmoss, ue, commerce, ecommerce
Requires at least: 4.0.0
Tested up to: 5.2.x
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Documentation: https://docs.yithemes.com/yith-woocommerce-eu-vat/

== Changelog ==

= Version 1.4.0 - Released: Aug 05, 2019 =

* New: support WooCommerce 3.7
* New: added new field in the user account to store the VAT number
* New: Added filter 'ywev_vat_already_validated_and_valid' to validate extra fields before apply VAT exemption.
* Tweak: replacing magic methods by getters and setters
* Tweak: New parameters to filters ywev_vat_already_validated_and_valid and ywev_vat_validation_response
* Tweak: added the VAT Number field in the user edit page on admin side
* Update: updated plugin core
* Fix: fixed php warnings in the code
* Dev: added plugin upgrade submodule

 
= Version 1.3.8 - Released: Apr 09, 2019 =

* New: support to WooCommerce 3.6.0 RC 1
* Update: Italian language
* Update: updated Plugin Framework
* Fix: fixed the shipping tax displayed in the order
* Fix: Correct the tax value in the cart
* Fix: fixing the shipping tax included in the total

= Version 1.3.7 - Released: Feb 19, 2019 =

* New: added the fee taxes to the tax exemption
* Tweak: added a new condition to check the EU VAT number
* Update: Updated Dutch translation
* Update: updated Plugin Framework
* Fix: prevent issue (notice and vat discount) when vat number is not set at checkout page
* Fix: fixing a possible negative VAT in the order
* Fix: fixing warnings in the order metabox
* Dev: added a new filter in the ywev-checkout enqueue script

= Version 1.3.6 - Released: Dec 07, 2018 =

* New: support to WordPress 5.0
* Update: plugin core to version 3.1.6

= Version 1.3.5 - Released: Oct 23, 2018 =

* Update: plugin framework
* Update: plugin description
* Update: plugin links

= Version 1.3.4 - Released: Oct 17, 2018 =

* New: Support to WooCommerce 3.5.0
* Tweak: new action links and plugin row meta in admin manage plugins page
* Update: updated the official documentation url of the plugin
* Update: Dutch language
* Update: updating the parse_str to the version 7.2 of php
* Update: Updated plugin-fw
* Fix: remove some text and slug
* Fix: fixing the shipping tax value in the order page
* Dev: fix variable name
* Dev: adding checks to $eu_vat_dat
* Dev: checking YITH_Privacy_Plugin_Abstract for old plugin-fw versions

= Version 1.3.3 - Released: May 25, 2018 =

* GDPR:
   - New: exporting user question and answers data info
   - New: erasing user question and answers data info
   - New: privacy policy content
* New: option to disable vat exception process for same country (ajax call is not processed and any message showed under the field)
* Tweak: set eu vat fields using woocommerce field filter
* Update: italian Translation
* Update: dutch language
* Update: documentation link of the plugin
* Update: minify js
* Fix: plugin description
* Fix: vat execption is always processed, also if country is disabled
* Dev: Added a filter in the VAT validation response

= Version 1.3.2 - Released: Feb 21, 2018 =

* Update: JS Unminifed version
* Fix: Fix a problem with the Greece country code and VAT code
* Fix: Fixing the import standard taxes button, now don't delete the previous taxes
* Dev: eu vat field print using 'woocommerce_billing_fields' filter

= Version 1.3.1 - Released: Dic 22, 2017 =

* Update: Compatibility with subscription - remove taxes when vat applied

= Version 1.3.0 - Released: Nov 08, 2017 =

* New: support to WooCommerce 3.2.3
* Fix: VAT not redeemed from shipping costs

= Version 1.2.25 - Released: Sep 09, 2017 =

* Update: plugin framework
* New: option to set label for eu vat field
* New: option to set placeholder for eu vat field
* New: option to set description for eu vat field

= Version 1.2.24 - Released: Jul 28, 2017 =

* Fix: Taxes report not working
* Tweak: Moved taxes report on a new tab

= Version 1.2.23 - Released: Jul 12, 2017 =

* Fix: unable to view vat information on orders with physical products
* Update: plugin framework

= Version 1.2.22 - Released: Jun 06, 2017 =

* New: support for WooCommerce 3.1.
* Tweak: tested up to WordPress 4.8.

= Version 1.2.21 - Released: May 16, 2017 =

* Fix: paid tax amount not shown on VAT report.
* Fix: report not correctly exported as CSV file.

= Version 1.2.20 - Released: May 08, 2017 =

* Fix: the message about VAT number not valid shown automatically on page load.
* Tweak: do not trigger several AJAX call while entering the VAT Number.

= Version 1.2.19 - Released: Apr 30, 2017 =

* Update: YITH Plugin Framework.
* Fix: VAT validation not triggered when a previous VAT number is deleted.

= Version 1.2.18 - Released: Apr 05, 2017 =

* Fix: YITH Plugin-fw initialization

= Version 1.2.17 - Released: Mar 08, 2017 =

* New:  Support to WooCommerce 2.7.0-RC1
* Update: YITH Plugin Framework
* Fix: VAT number prefixed by country code and VAT number with spaces were not processed correctly.

= Version 1.2.16 - Released: Feb 14, 2017 =

* New: 'Default Customer Location' option is automatically set to 'Geolocate' in order to use the mandatory geolocation service on checkout page.
* Fix: country confirmation checkbox not shown on checkout page in particular conditions.

= Version 1.2.15 - Released: Feb 09, 2017 =

* Fix: option 'Forbid EU customer checkout' not applied on checkout.

= Version 1.2.14 - Released: Jan 30, 2017 =

* New: ask for country confirmation if the IP address does not match with the billing country
* New: an option to choose if the VAT number field has to be shown to customers from the same country of the shop itself
* New: template 'yith-eu-vat/country-confirmation.php' lets you customize the country confirmation checkbox in checkout page
* Tweak: country verification applied basing on billing country and geo-localized country before validating the EU VAT number
* Remove: option "Customer's country check"
* Remove: option 'EU VAT number check'
* Dev: filter 'yith_ywev_default_tax_rates' lets you set the default tax rates

= Version 1.2.13 - Released: Dec 07, 2016 =

* Added: ready for WordPress 4.7
* Updated: language files

= Version 1.2.12 - Released: Oct 31, 2016 =

* Added: new option for VAT exemption on physical products
* Updated: VAT rate table updated for all EU countries
* Updated: Allow customers who do not reside in an EU country to checkout without a VAT number even if the plugin option is set to ask a mandatory VAT number
* Fixed: VAT number is not asked anymore as a mandatory field if there aren't digital goods on the cart

= Version 1.2.11 - Released: Jun 13, 2016 =

* Updated: WooCommerce 2.6 100% compatible
* Updated: VAT number starting with the country code will be cleaned before the validation
* Updated: removed use of action 'woocommerce_update_option' when saving the plugin options

= Version 1.2.10 - Released: Apr 19, 2016 =

* Fixed: the checks for VAT exemption works at checkout page but sometimes fails during the following order creation

= Version 1.2.9 - Released: Apr 18, 2016 =

* Updated: ready for WordPress 4.5
* Fixed: warning on missing index for custom base location

= Version 1.2.8 - Released: Jan 15, 2016 =

* Fixed: function call not supported by PHP version prior to 5.4

= Version 1.2.7 - Released: Jan 08, 2016 =

* Updated: plugin ready for WooCommerce 2.5
* Fixed: unable to change tax classes monitored by the plugin

= Version 1.2.6 - Released: DEC 26, 2015 =

* Fixed: JS script not checks the VAT number validation when the plugin settings do not ask for country validation
* Updated: CSS font-size for error messages

= Version 1.2.5 - Released: Nov 23, 2015 =

* Updated: changed action used for YITH Plugin FW loading from after_setup_theme to plugins_loaded

= Version 1.2.4 - Released: Nov 02, 2015 =

* Update: changed text-domain from ywev to yith-woocommerce-eu-vat
* Update: YITH plugin framework

= Version 1.2.3 - Released: Oct 26, 2015 =

* Update: changed the webservice used for EU VAT validation

= Version 1.2.2 - Released: Oct 01, 2015 =

* Fix: removed wp_die.

= Version 1.2.1 - Released: Aug 12, 2015 =

* Tweak: update YITH Plugin framework.

= Version 1.2.0 - Released: May 21, 2015 =

* Fix : support WooCommerce plugin version prior than 2.3.0

= Version 1.1.0 - Released: Apr 22, 2015 =

* Fix : security issue (https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage/)
* Tweak : support up to WordPress 4.2

= 1.0.0 =

Initial release