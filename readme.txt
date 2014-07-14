=== WP SecureSubmit ===
Contributors: markhagan
Tags: donation, donations, payment, buy now, gateway, heartland
Requires at least: 3.0.1
Tested up to: 3.9.1
Stable tag: trunk
License: Custom
License URI: https://github.com/SecureSubmit/magento-extension/blob/master/LICENSE

SecureSubmit allows merchants using Heartland Payment Systems to take PCI-Friendly donations on their WordPress site.

== Description ==

SecureSubmit makes it simple to start taking tokenized, PCI-Friendly payments (no card data ever touches your web server 
in any way) without making your visitors leave your site and without using iFrames.

Features of SecureSubmit:

*   Only two configuration fields: public and secret API key
*   Simple to isntall and configure
*   "Buy Now" options supported
*   "Donate Now" is default, tagless option
*   Fully supported by Heartland Payment Systems
*   Available as a form or as a modal window


== Installation ==

1. Upload `SecureSubmit` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[securesubmit]` in your templates

== Frequently Asked Questions ==

= How do I get started? =

Get your Certification (Dev) API keys by creating an account by [Clicking Here](here: https://developer.heartlandpaymentsystems.com/SecureSubmit/ "Heartland SecureSubmit")

== Screenshots ==

1. This is an example of a "Buy Now" button configuration.
2. This shows what a "Buy Now" modal looks like.
3. This screenshot shows a "Donate Now" (the default [securesubmit] tag) view.
4. This screenshot shows a non-modal view.

== Changelog ==

= 1.2.1 =
* Updated version of DB

= 1.2.0 =
* Added Admin view of transactions.
* Default amount can now be modified.
* FAQ section added to admin.
* Plugin now has a side menu option in the admin.
* Hiding amount fields after submission of payment.
* Upgraded version of jQuery tokenization library.
* From email address is now configurable from settings.

= 1.1.6 =
* Allowing non-modal users to configure the header text.
* amountdefault attribute added for default donation amounts.
* productimage can now be set to "none".

= 1.1.5 =
* Internet Explorer 6-9 placeholder fix.
* CSS Fix for additional style clearing.

= 1.1.4 =
* Updated styles to clear textbox attributes.

= 1.1.3 =
* Added work around for sites running line break conversion plugins.

= 1.1.2 =
* Added RAW tags to ignore line breaks.

= 1.1.1 = 
* Fixed multiple buttons on the same page.
* Added dropdown as an additional option type.

= 1.1.0 =
* Added some screenshots of the plugin in action.
* Added ability to change button text.
* Added additional option types.

= 1.0.0 =
* Initial Release

== Tag Options ==

1. modal="true/false"
2. productid="value" (this is required for non-donation)
3. productname="value"
4. buttontext="value"
5. requireshipping="value"
6. productimage="value" (default is a picture of a gift box)
