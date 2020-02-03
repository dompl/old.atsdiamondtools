=== AG Barclaycard ePDQ - WooCommerce Gateway ===
Contributors: We are AG, freemius
Requires at least: 4.0
Tested up to: 5.2
Stable tag: 4.2

== Description ==

We are AG's flagship plugin (where it all began), this plugin will allow you to process orders using the Barclays ePDQ system on a WooCommerce enabled website. 

== Installation ==

Setting up this plugin should only take 30 minuties to complete, follow the steps over at the plugin documentation page [here] (https://we-are-ag.helpscoutdocs.com/article/45-setup)

== Frequently Asked Questions ==

= Does this plugin work with newest WP and WooCommerce versions? =
Yes, this plugin works really fine with the latest versions!

= I have a problem with the plugin, its not working =
Ok not a problem, lets see if you can fix it by following these steps.
1. Enable the plugins debug mode in the settings page.
2. Process a test transaction using the gateway.
3. Check the plugins error logs to see if any errors have been logged, if they have follow the steps to fix.
4. Issue is still not fixed? Check the ePDQ error log in the back office.
5. Follow the steps in the trouble shooting section of the documentation to fix.
6. Still having issues? Send us a support ticket detailing the issue you have, we may ask for screenshots of the ePDQ back office.

= Can I process refunds using this plugin? =
Yes! You can process refunds right from the edit order screen.

= Dose this plugin work with WooCommerce Subscriptions? =
Yes!

== Upgrade Notice ==

Automatic updates should work like a charm; as always though, update on a staging site before pushing to a live site, failing that ensure you backup your site just in case.

== Changelog ==

= v3.1.5 - 21/10/19 =
* New           - Support for Direct HTTP server-to-server request with a fallback to docs to show the setup guide.
* New           - Extra order transaction logging, follow the customer process via AG error logs, useful to track any setup errors.
* New           - New WooCommerce order notices for non-successful order.
* New           - New setting to layout payment methods on ePDQ payment page.
* New           - Feature request link and information added to the AG welcome screen.
* Update        - Update to FS SDK.

= v3.1.4 - 02/10/19 =
* Dev           - Added null coalescing operator to order_notes array.
* Dev           - Backward compatible with older versions of Sequential Order Numbers Pro
* New           - New ePDQ error case catch.
* Fix           - Get correct admin URL when admin is not in root.

= v3.1.2 - 16/09/19 =
* New           - Activated a new affiliate program for plugin, clients can sign up within the plugin welcome screen and earn commission. 
* New           - New affiliate section added to welcome screen. 
* Support       - Support for Sequential Order Numbers Pro plugin.

= v3.1.1 - 16/07/19 =
* New           - Added both Diners and Discover card icons. 

= v3.1.0 - 20/06/19 =
* New           - Display a notice with icon to customer of redirect to Barclays server (off by default). 
* Enhancement   - Improvements to debug mode, logs are not part of WooCommerce log system.
* Enhancement   - Improvements to settings for better performance.
* Enhancement   - Improvements to check_response for better performance.
* Dev           - Added new assets folder for better structure for plugin CSS. 
* Dev           - New CSS files for admin and website frontend. 
* Fix           - Fixed issue with WooCommerce Subscriptions sites and SHA.
* Tweak         - New define_constants() to help loading plugin files.
* Feature       - Users can now enrol to become a beta tester of new versions of plugin.
* Change        - Spelling, function name changes and changes to readme file. 
* Update        - Update to FS SDK   

= v3.0.1 - 25/04/19 =
* Fix           - Fixed issue with non WooCommerce Subscriptions sites. 

= v3.0.0 - 24/04/19 =
* New           - Implemented support for WooCommerce Subscriptions. 
* Tweak         - Limit characters in customer fields (ePDQ has a max limit). 
* Tweak         - Remove apostrophe from customer last name.
* Tweak         - PHP 7.3 compatibility improvements.
* Dev           - Added new folder for better structure for plugin classes. 
* Dev           - New helper, settings, subscriptions and crypt classes. 
* Enhancement   - Improvements to RIPEMD encryption.
* Enhancement   - Improvements to successful transaction order data.
* Enhancement   - New error logging for not setup correctly.
* Feature       - Implemented third level of security using Sodium, PHP version 7.2 or above is required for this to work. 
* Checking      - Checking support for new WooCommerce version 3.6+ (working with latest version).
* Change        - Changed location of debug file location. 

= v2.14.4 - 21/03/19 =
* Tweak     - Convert all accent customer name characters to ASCII characters. 
* New - New debug feature to log any errors in a error log file (Helps to find any issues with setup).
* Feature - Multi-currency support (the use of an multi-currency ePDQ account is needed).
* Enhancement - Two new functions for order notes and order meta data (Performance improvement).
* Tweak   - Text changes, typo's fixed. 


= v2.14.2 - 18/03/19 =
* Fix     - WP nonce issue was replaced with custom security hash. 

= v2.14.0 - 07/03/19 =
* Feature - Implemented second level of security using nonces, this is on by default. 
* Feature - Implementation of update early warning feature, able to display warnings about updates and security patches.
* Fix     - Fixed issue with some refunds not processing.

= v2.13.2 - 27/02/19 =
* Tweak   - Text changes, typo's fixed.   
* Enhancement  &nbsp;- Improvements to AG core classes   
* Update   - Update to FS SDK   

= v2.13.0 - 28/01/19 =
* Enhancement   - Improvements to welcome screen   
* Change   - PHP compatability changes   
* Change   - FS SDK update   
* Fix   - Typos   

= v2.12.2 - 08/11/18 =
* Dev   - Plugin has been rewritten.   
* Change   - Brand new welcome page with help and info for new/old users.   
* Fix   - ePDQ changed requirements for refund API call, changes added (NEW field in settings).   
* Enhancement   - improvements to refund error notices.   
* Feature   - The use of dynamic templates, new field in settings which will enable you to send dynamic template to ePDQ.   
* Checking   - Checking support for new WooCommerce version 3.5+ (working with latest version).   

= v2.11.1 - 27/10/18 =
* Fix   - Fixed issue with card icons not showing.   

= v2.11.0 - 22/10/18 =
* Feature   - Update to new licence system to stop the old licence system look up.   

= v2.10.2 - 06/08/18 =
* Change   - Added notice about plugin docs to welcome page.   

= v2.10.1 - 18/07/18 =
* Fix   - Removed prefix as causing issues with invoice plugins, prefix can be done with custom function in theme.   

= v2.10.0 - 14/07/18 =
* New   - Select card brands to be shown on checkout page.   
* Fix   - Fixed issue with some users entering hyphenated &amp; apostrophes addresses   

= v2.9.11 - 23/05/18 =
* New   - GDPR - enable user to remove meta data stored about order   
* New   - GDPR - Some information added to privacy content   
* Notice   - Removal of plugin notice in dashboard   

= v2.9.10 - 10/05/18 =
* Fix   - Fixed security issue.   
* Notice   - Removal of showcase submit   
* Checking support for new WooCommerce version 3.4+ (working with new version)   

= v2.9.9 - 22/03/18 =
* New   - Adding of new error notices for processing refunds   
* Checking support for new WooCommerce version 3.3.4 (working with new version)   

= v2.9.8 - 07/03/18 =
* Fix   - Fixed issue with refund settings not showing.   

= v2.9.7 - 26/02/18 =
* Checking support for new WooCommerce version 3.3.2 (working with new version)   
* Fix   - Fixed issue with prefix, "-" removed.   

= v2.9.6 - 07/02/18 =
* Checking support for new WooCommerce version 3.3.1 (working with new version)   
* Fix   - Fixed issue with prefix breaking orders from being marked as paid.   
* Change   - Changed function for refunds with live/test urls   

= v2.9.5 - 30/01/18 =
* Checking support for new WooCommerce version 3.3+ (working with new version)   

= v2.9.4 - 29/01/18  =
* Fix   - Fixed live mode payment issue - get_epdq_status_code   
* Notice   - Prep work for adding subscription payments to plugin.   

= v2.9.3 - 25/01/18  =
* Fix   - Fixed issue with test details always showing   

= v2.9.2 - 23/01/18  =
* Feature   - Add test mode notice with test card numbers to gateway description.   
* Fix   - Fixed bug with refunds not processing.   

= v2.9.0 - 22/01/18  =
* Feature   - Process refunds direct from the WooCommerce admin panel.   

= v2.8.0 - 10/12/17  =
* Feature   - Pay for order screen auto redirects to Barclays server.   
* New   - Declaring required and supported WooCommerce version   
* Change   - Update to licence system   

= v2.7.6 - 26/8/17  =
* Notice   - Notice about Direct link plugin   

= v2.7.5 - 17/05/17  =
* Change   - Function clean up - remove of old functions not used.   

= v2.7.4 - 18/04/17  =
* Fix   - URL change for update check   
* Fix   - Licence key constant fixed   
* Fix   - Add notice for custom logo needing to be on SSL   
* Change   - Changed ref of WooCommerce version to 3.0   

= v2.7.0 – 24/02/17  =
* Feature   - Option to add order prefix to both WooCommerce and send prefix to Barclays gateway   

= v2.6.3 – 01/01/17  =
* Change   - Updated plugin along with new WooCommerce CRUD system (Coming in 2.7)   

= v2.6.2 – 18/10/16  =
* Fix   - Licence system bug   

= v2.6.1 – 15/08/16  =
* Feature   - update to licence system, enabled notice about licence to user   

= v2.6.0 – 07/07/16  =
* Feature   - Added logo field to plugin settings to show logo on payment page   

= v2.5.5 – 27/04/16  =
* Fix   - Removed debug message from failed activation.   

= v2.5.4 – 11/04/16  =
* Feature   - Add of new welcome page with full plugin set up help.   
* Feature   - Enable website submit for new plugin showcase.   
* Clean up   - Final bits gone from old repo system.   

= v2.5.3 – 08/04/16  =
* Fix   - Issue with order notes &amp; "payment_complete()"   
* Clean up   - Clean up of old functions   

= v2.5.2 – 18/03/16  =
* Fix   - Issue with non SSL sites   

= v2.5.1 – 24/02/16  =
* Update to bring plugin to WordPress 4.4.2 and WooCommerce 2.5.2   
* Clean up of settings page   
* Feature - License system added   
* Clean up old repo system   

= v2.1.1 – 09/12/15  =
* Update to bring plugin to work with WordPress 4.4   

= v2.1.0 – 29/09/15  =
* Fix – Removed unused sections from admin screen.   
* Fix - Tweaked process for better results.   
* Feature – Add option for tracking, better debuging.   
* Feature – Changed to new Repo.   
* Feature – Added RSS feed.   

= v2.0.1 - 31/7/15 =
* First release   
                  