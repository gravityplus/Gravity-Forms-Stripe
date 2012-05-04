=== Gravity Forms Stripe Add-On ===
Contributors: naomicbush
Donate link: http://naomicbush.com/
Tags: form, forms, gravity, gravity form, gravity forms, gravityforms, stripe, payment, payments, subscribe, subscriptions, recurring billing, paypal, authorize.net, credit cards, online payment
Requires at least: 3.3.1
Tested up to: 3.3.1
Stable tag: 0.1.3

Easy and secure credit card payments on your WordPress site with Stripe and Gravity Forms!

== Description ==

[Stripe](https://stripe.com) allows you to process credit cards directly on your site, securely and easily, without having to deal with merchant accounts, PCI-compliance, or PayPal. This Gravity Forms add-on integrates Stripe with your forms (adapted from the Gravity Forms Authorize.net Add-On).

> This plugin is an add-on for the [Gravity Forms plugin](http://naomicbush.com/getgravityforms "visit the Gravity Forms website").
> [Grab a license](http://naomicbush.com/getgravityforms "purchase Gravity Forms!") if you don't already have one - you'll thank me later :-)
>
> You'll also need a [Stripe](https://stripe.com) account.

Requires WordPress 3.3.1, PHP 5.3 (although it worked for me when initially tested on a 5.2 server), and Gravity Forms 1.6.3.3

**Current Features**

* One-time payments
* Recurring/Subscription Payments

**Upcoming Features**
* Receipts
* [Make a request](http://naomicbush.com/)

**Support**

* Please note that this plugin has not been extensively tested so use at your own risk - I needed it for a few projects and it works for me.

* Feel free to leave a message in the forum, or open an issue or pull request on [GitHub](https://github.com/naomicbush/Gravity-Forms-Stripe) - no guarantees, though

* If you want to be sure that I see & respond to your request or issue, I consider [donations](http://naomicbush.com) a fair exchange :-)


**Current Limitations**

* Subscription product can be the *only* product on the form
* When canceling a subscription through the Gravity Forms entry detail screen, the subscription will remain active in Stripe until the end of the period, however it will reflect in WordPress as 'canceled'

**Known Issues**

* https://github.com/naomicbush/Gravity-Forms-Stripe/issues


== Installation ==

This section describes how to install and setup the Gravity Forms Stripe Add-On.

1. Upload `gravity-forms-stripe` to the `/wp-content/plugins/` directory
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

== Screenshots ==

1. Settings page
2. Credit Card field
3. Stripe feed

== Changelog ==
= new version number =
* Process subscriptions/recurring payments
* Create a customer in Stripe for all transactions

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
= 0.1.3 =
Important fix for conflicts with other GF payments add-ons! Please upgrade to the latest version.

= 0.1.2 =
Important fix for error handling! Please upgrade to the latest version.

= 0.1.1 =
Important fix! Please upgrade to the latest version or your form may not correctly process payments.

