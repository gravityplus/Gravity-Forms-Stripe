=== Gravity Forms + Stripe ===
Contributors: naomicbush
Donate link: http://gravityplus.pro/
Tags: form, forms, gravity, gravity form, gravity forms, gravityforms, stripe, payment, payments, subscribe, subscriptions, recurring billing, paypal, authorize.net, credit cards, online payment
Requires at least: 3.4.1
Tested up to: 3.4.3
Stable tag: 1.6.9.1

Easy and secure credit card payments on your WordPress site with Stripe and Gravity Forms!

== Description ==

[Stripe](https://stripe.com) allows you to process credit cards directly on your site, securely and easily, without having to deal with merchant accounts, PCI-compliance, or PayPal. This Gravity Forms add-on integrates Stripe with your forms (adapted from the Gravity Forms Authorize.net Add-On).

> This plugin is an add-on for the [Gravity Forms plugin](http://gravityforms.com "visit the Gravity Forms website").
> [Grab a license](http://gravityforms.com "purchase Gravity Forms!") if you don't already have one - you'll thank me later :-)
>
> You'll also need a [Stripe](https://stripe.com) account.

Requires WordPress 3.4.1, PHP 5.3, and Gravity Forms 1.6.9

**Current Features**

* One-time payments
* Hooks to extend the plugin and add in your own functionality

**Want [more Stripe](http://gravityplus.pro)?**

* Recurring payments/subscriptions
* Coupons
* Invoice line items
* Stripe + PayPal option on same form
* Pretty receipts?
* [Get More Stripe here](http://gravityplus.pro)

**Support**

* Do NOT contact Gravity Forms OR Stripe for help with this add-on. They do not provide support for this plugin.
* Full, paid support IS available at [gravity+](http://gravityplus.pro).
* Support is not provided for free or via the forums here, but feel free to help one another.

**Current Limitations**

* Cannot have Stripe Add-On "activated" at the same time as Authorize.Net or PayPal Pro Add-Ons
* For security reasons, credit card field has to be on the last page of a multi-page form
* Can only setup 1 Stripe feed per form

**Known Conflicts**

* Shortcodes Ultimate
* poorly coded Themeforest themes

**Supporters**

[daveshine](http://profiles.wordpress.org/daveshine/), [admodiggity](http://profiles.wordpress.org/admodiggity/), [pnommensen](http://profiles.wordpress.org/pnommensen/), Linda C., [jacobdubail](http://profiles.wordpress.org/jacobdubail/), Michael S., Mark C., [willshouse](http://profiles.wordpress.org/willshouse), Dan B., Aaron A., [wpcdn](http://profiles.wordpress.org/wpcdn), [feshin](http://profiles.wordpress.org/feshin), Scot R., Teresa O.


== Installation ==

This section describes how to install and setup the Gravity Forms Stripe Add-On.

1. Upload `gravityforms-stripe` to the `/wp-content/plugins/` directory
2. Make sure that Gravity Forms is activated
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add your Stripe API keys to the Stripe tab on the Settings page (Forms->Settings).
5. Create a form, adding at least one product field along with the new 'Credit Card' field that appears under 'Pricing Fields.' Keep in mind that Stripe accepts a minimum charge of $0.50 - this means that the total amount of your form must be at least $0.50.
6. Under Forms->Stripe, add a Stripe feed for your new form.

== Frequently Asked Questions ==

= Do I need to have a copy of Gravity Forms for this plugin to work? =
Yes, you need to install the [Gravity Forms Plugin](http://bit.ly/getgravityforms "visit the Gravity Forms website") for this plugin to work.

= What is the minimum amount my form can accept? =
$0.50, [per Stripe](https://stripe.com/help/faq)

= Does this version work with the latest version of Gravity Forms? =
Just look at the version number! The versioning scheme now follows that of Gravity Forms so if the version number starts with *1.6.7.1*, then the Add-On has been tested up to *Gravity Forms 1.6.7.1*. An additional number at the end indicates the change number of the Add-On itself.

= Why am I getting in 'Invalid token ID' error? =
This error occurs when the Stripe JS is blocked from running by a theme or another plugin. Follow the procedure outlined here by Gravity Forms in order to troubleshoot: http://www.gravityhelp.com/documentation/page/Testing_for_a_Theme/Plugin_Conflict with one minor change -- For plugin conflicts, deactivate all plugins except Gravity Forms and Gravity Forms + Stripe.

== Screenshots ==

1. Settings page
2. Credit Card field
3. Stripe feed

== Changelog ==
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
= 1.6.9.1 =
Important fix for all outstanding issues to date. Please upgrade to the latest version or your form may not correctly process payments.

= 0.1.3 =
Important fix for conflicts with other GF payments add-ons! Please upgrade to the latest version.

= 0.1.2 =
Important fix for error handling! Please upgrade to the latest version.

= 0.1.1 =
Important fix! Please upgrade to the latest version or your form may not correctly process payments.

