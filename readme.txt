=== WooCommerce Dynamic Pricing ===
Contributors: hafizaqibg
Tags: woocommerce, pricing, discounts, wholesale, quantity discounts
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
WC requires at least: 8.0
WC tested up to: 9.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Quantity-tier and customer-role pricing for WooCommerce.

== Description ==

Adds two pricing mechanisms WooCommerce doesn't support natively:

* Per-product quantity discount tiers (e.g. 10% off at 5+, 20% off at 20+)
* A store-wide percentage discount for a chosen customer role (e.g. wholesale customers)

Discounts are applied at the price-filter level, so they're visible on the shop loop and product page, not just calculated silently at checkout.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate through the "Plugins" screen in WordPress.
3. Configure quantity tiers on each product's "Dynamic Pricing" tab.
4. Configure the role discount under WooCommerce → Settings → Products → Dynamic Pricing.

== Changelog ==

= 1.0.0 =
* Initial release: quantity tiers and role-based discounts.
