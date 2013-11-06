=== Gravity Forms + Stripe ===
Contributors: naomicbush
Donate link: https://gravityplus.pro/gravity-forms-stripe
Tags: form, forms, gravity, gravity form, gravity forms, gravityforms, stripe, payment, payments, subscribe, subscriptions, recurring billing, paypal, authorize.net, credit cards, online payment
Requires at least: 3.6
Tested up to: 3.7.1
Stable tag: 1.7.11.2

Easy and secure credit card payments on your WordPress site with Stripe and Gravity Forms!

== Description ==

[Stripe](https://stripe.com) allows you to process credit cards directly on your site, securely and easily, without having to deal with merchant accounts, PCI-compliance, or PayPal. This Gravity Forms add-on integrates Stripe with your forms using [Stripe.js](https://stripe.com/docs/stripe.js) to make sure sensitive card information never hits your server.

> This plugin is an add-on for the [Gravity Forms plugin](http://gravityforms.com "visit the Gravity Forms website").
> You need to [grab a license](http://gravityforms.com "purchase Gravity Forms!") if you don't already have one - you'll thank me later :-)
>
> You'll also need a [Stripe](https://stripe.com) account.

Requires WordPress 3.5, PHP 5.3, and Gravity Forms 1.7.11. Works with WordPress Multisite.

**Current Features**

* One-time payments
* International Stripe accounts, including those with multiple currencies
* Stripe + PayPal option on same form
* *Save credit cards instead of charging them right away
* *Recurring payments/subscriptions
* *Multiple quantities of one subscription, e.g. 5 users at $10/month/user
* *One-time setup fee charge for subscriptions
* *Stripe coupons
* *Gravity Forms User Registration Add-On integration, including allowing logged-in users to manage their subscriptions
* *Stripe event notifications using Gravity Forms notifications system
* Hooks to extend the plugin and add in your own functionality

*available only with [More Stripe here](https://gravityplus.pro/gravity-forms-stripe)

**Support**

* Although I am unable to monitor the forums here or provide free support, **full, paid support is available at [gravity+](https://gravityplus.pro)**. I am very happy to help.
* Also, neither Gravity Forms nor Stripe provides support for this plugin.
* If you think you've found a bug, feel free to contact me.

**Current Limitations**

* Cannot have Stripe Add-On "activated" at the same time as Authorize.Net or PayPal Pro Add-Ons
* For security reasons, credit card field has to be on the last page of a multi-page form
* One Stripe form per page

**Reported Conflicts**

* plugin: Shortcodes Ultimate
* plugin: PHP Shortcode Version 1.3
* theme: Themeforest themes that strip shortcodes
* plugin: Root Relative URLs

**Supporters**

[daveshine](http://profiles.wordpress.org/daveshine/), [admodiggity](http://profiles.wordpress.org/admodiggity/), [pnommensen](http://profiles.wordpress.org/pnommensen/), Linda C., [jacobdubail](http://profiles.wordpress.org/jacobdubail/), Michael S., Mark C., [willshouse](http://profiles.wordpress.org/willshouse), Dan B., Aaron A., [wpcdn](http://profiles.wordpress.org/wpcdn), [feshin](http://profiles.wordpress.org/feshin), Scot R., Teresa O.


== Installation ==

This section describes how to install and setup the Gravity Forms Stripe Add-On. Be sure to follow *all* of the instructions in order for the Add-On to work properly.

1. Upload the `gravity-forms-stripe` folder to the `/wp-content/plugins/` directory
2. Make sure that Gravity Forms is activated
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add your Stripe API keys to the Stripe tab on the Settings page (Forms->Settings).
5. Create a form, adding at least one product field along with the new 'Credit Card' field that appears under 'Pricing Fields.' Keep in mind that Stripe accepts a minimum charge of $0.50 - this means that the total amount of your form must be at least $0.50.
6. Under Forms->Stripe, add a Stripe feed for your new form.

== Frequently Asked Questions ==

= Do I need to have a copy of Gravity Forms for this plugin to work? =
Yes, you need to install the [Gravity Forms Plugin](http://gravityforms.com "visit the Gravity Forms website") for this plugin to work.

= What is the minimum amount my form can accept? =
$0.50, [per Stripe](https://stripe.com/help/faq)

= Does this version work with the latest version of Gravity Forms? =
Just look at the version number! The versioning scheme now follows that of Gravity Forms so if the version number starts with *1.7.9.1*, then the Add-On has been tested up to *Gravity Forms 1.7.9*. An additional number at the end indicates the change number of the Add-On itself.

= Does your plugin use Stripe.js? =
Yes.

= Do I need to have SSL? =
Yes, according to the Stripe Terms of Service regarding PCI-compliance.

= Why am I getting an 'Empty string given for card' error? =
Here are a few possible reasons, listed in the order they are most likely to occur:

1. Your theme (especially if purchased from Themeforest) is stripping the shortcode, preventing the Stripe JS from working. Here's what you want to look for in your theme files (the code is in yellow) and disable by placing a '//' without the quotes in front of those lines:
http://kaptinlin.com/support/discussion/7420/gravity-forms-code-of-the-raw-shortcode-discussion-thread/p1

This code may also be in a file called shortcodes.php or ThemeShortcodes.php.

2. Another theme or plugin is modifying the standard Gravity Forms dropdowns and removing the classes, which breaks the Stripe JS. You'll want to contact the theme author to learn how to prevent this.

3. You've embedded your Gravity Form directly into the page and missed one of the Gravity Forms instructions — happens to the best of us! Here are the instructions: http://www.gravityhelp.com/documentation/page/Embedding_A_Form

4. Another plugin is preventing the JS from working. Follow the procedure outlined here by Gravity Forms in order to troubleshoot: http://www.gravityhelp.com/documentation/page/Testing_for_a_Theme/Plugin_Conflict with one minor change -- For plugin conflicts, deactivate all plugins except Gravity Forms and Gravity Forms + Stripe.

5. JavaScript is not available to browser

= Do I need to purchase +(More) Stripe to make this plugin work? =
No. If you are having an issue and tried the troubleshooting steps, [purchase support](https://gravityplus.pro/). +(More) Stripe will *not* fix your issue --
it only adds features.

= Your plugin just does not work =
I can assure you that is not the case -- [this should help you find where the problem is occurring](http://scribu.net/wordpress/wordpress-plugin-troubleshooting-flowchart.html) or [this](http://uproot.us/docs/how-to-troubleshoot-plugin-issues/)

== Screenshots ==

1. Settings page
2. Credit Card field
3. Stripe feed

== Changelog ==
= 1.7.11.2 =
* Fix critical issue resulting from undocumented Stripe API change
* Bump version number

= 1.7.11.1 =
* Add new conditional logic options and fields for Stripe feed
* Add notice for incorrect version of Gravity Forms
* Update Stripe PHP library to 1.8.3 since 1.9.0 has issues
* Update for GF1.7.11
* Update for WordPress 3.7
* Bump version number

= 1.7.10.1 =
* Ensure GF1.7.10 compatibility
* Bump version number

= 1.7.9.1 =
* Add hook for customer description: gfp_stripe_customer_description
* Add hook for charge description: gfp_stripe_customer_charge_description
* Add hook for live mode error messages: gfp_stripe_error_message
* Add charge creation override
* Add check for curl when plugin activated
* Add filter 'gfp_stripe_display_billing_info'
* Add action 'gfp_stripe_set_validation_result'
* Add PSR-0 autoloader
* Add UI improvements
* Add PHPDoc to all the things!
* Add PressTrends
* Add check for Gravity Forms when plugin activated
* Add Gravity Forms deactivation prevention if Stripe Add-On is still activated
* Add support for Stripe accounts with multiple currencies
* Add Gravity Forms Logging Tool integration
* Update Stripe PHP API library to 1.8.4
* Update 'gfp_stripe_customer_description' hook to pass all of the submitted form data, and not just the name
* Update 'gfp_stripe_create_error_message' to show actual card error in live mode, since they are safe to show per Stripe API
* Update 'gfp_stripe_customer_description' hook parameters to replace $form_data with $form
* Refactor & reorganize code
* Rename hook 'gform_stripe_action_fields' to 'gfp_stripe_feed_options'
* Rename hook 'gform_stripe_add_option_group' to 'gfp_stripe_feed_setting'
* Rename hook 'gfp_stripe_after_submission_update_lead' to 'gfp_stripe_entry_created_update_lead'
* Rename hook 'gfp_stripe_gform_after_submission' to 'gfp_stripe_entry_created_subscriber_id'
* Rename hook 'gfp_stripe_after_submission_insert_transaction_type' to 'gfp_stripe_entry_created_insert_transaction_type'
* Fix hook 'gfp_stripe_gform_after_submission' to include correct return value
* Fix undefined variable notice on stats page
* Fix PHP warnings
* Fix Stripe JS to get correct address fields from feed
* Fix Stripe condition not properly handling checkboxes and dropdowns
* Fix double form submissions if AJAX and 2+ forms on a page
* Move after submission processing from gform_after_submission to gform_entry_created
* Remove KLogger
* Remove currency disable
* Remove Stripe JS check for address_field_required

= 1.7.2.3 =
* Fix IE9 JS issue preventing card number submission
* Prevent Stripe API key whitespace error by stripping whitespace from API keys
* Fix annoying PHP warnings
* Clean up duplicate and unneeded code

= 1.7.2.2 =
* Fix issue with billing address not being sent to Stripe
* Add new billing address city field to Stripe token creation
* Remove hidden condition for sending billing address state and country to Stripe

= 1.7.2.1 =
* Update JS for credit card field change
* Fix currency detection performance issue
* Use original Stripe error in test mode, pretty errors in live mode
* Allow multiple Stripe feeds for multiple address fields on one form
* Fix annoying PHP warnings
* Update Stripe PHP library to 1.8.0
* Bump version number to latest version of Gravity Forms

= 1.6.11.1 =
* Add support for Canadian Stripe accounts
* Fix annoying PHP warnings
* Update Stripe PHP library
* Bump version number to latest version of Gravity Forms

= 1.6.9.1 =
* Switch to new version scheme that follows Gravity Forms
* Create a customer in Stripe for all transactions
* Fix Stripe JS to work without AJAX
* Fix issue with plugin not deactivating on uninstall
* Update to work with new Gravity Forms 1.6 fields
* Remove deprecated Stripe token parameter
* Allow unactivated Stripe test accounts to use in Test mode only

= 0.1.3 =
* Fix credit card field conflict with other GF payment add-ons
* Load Stripe JS only when form with a credit card field *and* Stripe feed is loaded
* Fix removal of credit card expiration date from information sent to server
* Add validation check for cardholder name and address
* Don't process payment if total is less than $0.50

= 0.1.2 =
* Fix error handling

= 0.1.1 =
* Fix "Class 'Stripe' Not Found" error

= 0.1 =
* Initial release. Process charges (one-time payments) only.

== Upgrade Notice ==
= 1.7.11.2 =
Critical fix! Please upgrade or your form will not correctly process payments. Also check Stripe dashboard for wrong charge amounts.

= 1.7.11.1 =
New version available! Adds new conditional logic options and fields to Stripe feed.

= 1.7.10.1 =
New version available! Fixes several issues & adds support for Stripe accounts with multiple currencies.

= 1.7.2.3 =
Important fix for JS issue in IE9 that prevents successful form submission.

= 1.7.2.2 =
Important fix for billing address issue! Please upgrade to make sure billing address is sent to Stripe.

= 1.7.2.1 =
Important update for Gravity Forms 1.7. Please upgrade to the latest version or your form may not correctly process payments.

= 1.6.11.1 =
New version available! Adds support for Canadian Stripe accounts, fixes PHP warnings.

= 1.6.9.1 =
Important fix for all outstanding issues to date. Please upgrade to the latest version or your form may not correctly process payments.

= 0.1.3 =
Important fix for conflicts with other GF payments add-ons! Please upgrade to the latest version.

= 0.1.2 =
Important fix for error handling! Please upgrade to the latest version.

= 0.1.1 =
Important fix! Please upgrade to the latest version or your form may not correctly process payments.