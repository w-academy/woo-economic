=== Plugin Name ===
Contributors: iamkristian
Tags: woocommerce, e-conomic, economic, integration
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.0.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin will integrate the WooCommerce e-commerce system for Wordpress
with the financial system E-conomic.

== Description ==
Free yourself of manual tasks when extending your woocommerce installation
with the financial system E-conomic.

= Feature list: =

* Synchronization of products from E-Conomic to WooCommerce.
* Synchronization of products from WooCommerce to E-Conomic.
* Automatic creation of debtors in E-Conomic, when the customer is filling out the order flow.
* Automatic creation of invoices in E-Conomic when WooCommerce orders reach status DONE.
* Automatic posting the invoice in E-Conomic
* When refunding in WooCommerce, they are added to a seperate cashbook in E-Conomic.
* Posibility to add a shipping poduct order line to E-Conomic invoices
* Seperate E-Conomic cashbook for webshop orders, giving you the possibility to review orders before comitting them.

== Installation ==

Install the plugin like any other wordpress plugin. You will need to use the
uploader, since the plugin isn't currently available on the wordpress plugins
site. You will need to install it after WooCommerce.

= Compatibility =
This plugin have been tested OK up to:

* WooCommerce 1.6.6
* Wordpress 3.5

= Configuration =
Navigate to settings -> wooeconomic. Here you configure the plugin. You will need the following:

* Your E-Conomic agreement number.
* Your E-Conomic username.
* Your E-Conomic password.
* You need to add an e-conomic product group, for products you want to see in WooCommerce. This group is used when synchronizing products.
* You need to add a product in E-Conomic, for shipping. You can use an existing product.
* You need to add a cashbook in E-Conomic, for credit and debit. You can use an existing cashbook.
* You need to add a debtor group in E-Conomic, for customers you get through WooCommerce.
* You can add a debtor offset, if you want your WooCommerce customers to have a customer number with specific range.
* You can add a product offset, if you want your WooCommerce products in a specific range.
* You need to turn on the api setting in E-Conomic. You can see how to do that on http://www.e-conomic.dk/support

= Products =
Products can be synchronized two ways. So if you already have lots of products in your WooCommerce shop, you can synchronize them into E-Conomic. And the otherway  way around of course. There are a few things to be aware of though:

* The product group you setup in E-Conomic is used for synchronizing. All products from that group but the shipping product are synchronized.
* You must add a unique SKU to your products in WooCommerce, this is used as the product number in E-Conomic.

= How it works =
Using the plugin is simple, it takes care of those tedious repeating tasks.

* When a customer adds his information in an order, the customer is added to E-Conomic.
* When you are ready to ship an order, and change order status to COMPLETED, an invoice will be created in E-Conomic for the customer.
* If you check the "Create debtor payment" setting, the invoice is not put into the cashbook, but booked right away.
* If you credit the customer, by setting the order status to REFUND, a reciept is put into the cashbook and you have to book it yourself.
* You can synchronize products from WooCommerce to E-Conomic, by pressing the "WooCommerce Products -> E-Conomic"-button.
* You can synchronize products from E-Conomic to WooCommerce, by pressing the "E-Conomic -> WooCommerce Products "-button.


== Frequently Asked Questions ==

= Nothing yet =

== Screenshots ==

1. The configuration setup of the plugin

== Changelog ==

= 1.0.5 =

* Going open source
* Getting ready for wordpress.org
* Displaying errors on product synchronisation

= 1.0.4 =

* Validation of products before synchronisation

= 1.0.3 =

* Safeguards on create invoice
* Safeguards on debtor creation
* Cashbook
* Automatic debtor payments
* Debtor payments
* Product sync: eco->woo
* Product sync: woo->eco
* Product synchronisation

= 1.0.2 =

* Internal release

= 1.0.1 =

* Validations and SKU
* Create debtor on create customer hook
* Credit nota creation
* Invoice creation
* E-conomic integration
* Admin settings

== Upgrade Notice ==

= 1.0.5 =
You can upgrade seemlessly.
