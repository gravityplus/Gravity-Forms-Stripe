# Gravity Forms + Stripe
http://wordpress.org/plugins/gravity-forms-stripe/

[Stripe](https://stripe.com) allows you to process credit cards directly on your site, securely and easily, without having to deal with merchant accounts, PCI-compliance, or PayPal.

This [Gravity Forms](http://naomicbush.com/getgravityforms) add-on integrates Stripe with your forms using [Stripe.js](https://stripe.com/docs/stripe.js) to make sure sensitive card information never hits your server.

## Supporters
[deckerweb](https://github.com/deckerweb), [admodiggity](https://github.com/admodiggity), [pnommensen](https://github.com/pnommensen), [Linda C.](http://askmepc.com/), [jacobdubail](https://github.com/jacobdubail), [Michael S.](http://markandphil.com/), [Mark C.](http://bizelevator.com/), [willshouse](http://profiles.wordpress.org/willshouse), Dan B., Aaron A., [wpcdn](http://profiles.wordpress.org/wpcdn), [feshin](http://profiles.wordpress.org/feshin), Scot R., Teresa O.

## Features
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

## Requirements
* WordPress 3.6, tested up to 3.7.1, Multisite as well
* PHP 5.3
* Gravity Forms 1.7.11 - [Grab a license](http://naomicbush.com/getgravityforms "purchase Gravity Forms!") if you don't already have one
* [Stripe](https://stripe.com) account

## Support
* Although I am unable to provide free support, **full, paid support is available at [gravity+](https://gravityplus.pro)**. I am very happy to help.
* Also, neither Gravity Forms nor Stripe provides support for this plugin.
* If you think you've found a bug, feel free to contact me.

## Current Limitations
* Cannot have Stripe Add-On "activated" at the same time as Authorize.Net or PayPal Pro Add-Ons
* For security reasons, credit card field has to be on the last page of a multi-page form
* One Stripe form per page

## Reported Conflicts

* plugin: Shortcodes Ultimate
* plugin: PHP Shortcode Version 1.3
* theme: Themeforest themes that strip shortcodes
* plugin: Root Relative URLs

## Installation

1. Upload `gravityforms-stripe` to the `/wp-content/plugins/` directory
2. Make sure that Gravity Forms is activated
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add your Stripe API keys to the Stripe tab on the Settings page (Forms->Settings).
5. Create a form, adding at least one product field along with the new 'Credit Card' field that appears under 'Pricing Fields.' Keep in mind that Stripe accepts a minimum charge of $0.50 - this means that the total amount of your form must be at least $0.50.
6. Under Forms->Stripe, add a Stripe feed for your new form.

## Changelog
### 1.7.11.1 (October 31, 2013)
* Add new conditional logic options and fields for Stripe feed
* Add notice for incorrect version of Gravity Forms
* Update Stripe PHP library to 1.8.3 since 1.9.0 has issues
* Update for GF1.7.11
* Update for WordPress 3.7
* Bump version number

### 1.7.10.1 (October 3, 2013)
* Ensure GF1.7.10 compatibility
* Bump version number

### 1.7.9.1 (September 30, 2013)
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

### 1.7.2.3 (May 14, 2013)
* Fix IE9 JS issue preventing card number submission
* Prevent Stripe API key whitespace error by stripping whitespace from API keys
* Fix annoying PHP warnings
* Clean up duplicate and unneeded code

### 1.7.2.2 (May 2, 2013)
* Fix issue with billing address not being sent to Stripe
* Add new billing address city field to Stripe token creation
* Remove hidden condition for sending billing address state and country to Stripe

### 1.7.2.1 (May 1, 2013)
* Update JS for credit card field change
* Fix currency detection performance issue
* Use original Stripe error in test mode, pretty errors in live mode
* Allow multiple Stripe feeds for multiple address fields on one form
* Fix annoying PHP warnings
* Update Stripe PHP library to 1.8.0
* Bump version number to latest version of Gravity Forms

### 1.6.11.1 (January 11, 2013)
* Add support for Canadian Stripe accounts
* Fix annoying PHP warnings
* Update Stripe PHP library
* Bump version number to latest version of Gravity Forms

### 1.6.9.1 (November 1, 2012)
* Switch to new version scheme that follows Gravity Forms
* Create a customer in Stripe for all transactions
* Fix Stripe JS to work without AJAX
* Fix issue with plugin not deactivating on uninstall
* Update to work with new Gravity Forms 1.6 fields
* Remove deprecated Stripe token parameter
* Allow unactivated Stripe test accounts to use in Test mode only

### 0.1.3 (April 19, 2012)
* Fix credit card field conflict with other GF payment add-ons
* Load Stripe JS only when form with a credit card field *and* Stripe feed is loaded
* Fix removal of credit card expiration date from information sent to server
* Add validation check for cardholder name and address
* Don't process payment if total is less than $0.50

### 0.1.2
* Fix error handling

### 0.1.1
* Fix "Class 'Stripe' Not Found" error

### 0.1 (April 3, 2012)
* First release

## License
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to:

Free Software Foundation, Inc. 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.