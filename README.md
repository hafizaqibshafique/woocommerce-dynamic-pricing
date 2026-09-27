# WooCommerce Dynamic Pricing

A custom WooCommerce plugin for quantity-tier and customer-role pricing — the kind of pricing logic that WooCommerce doesn't handle out of the box and that most "dynamic pricing" plugins over-engineer (or paywall) for a need that's usually 200 lines of well-placed hooks.

## What it does

- **Quantity breaks per product**: e.g. 10% off at 5+ units, 20% off at 20+ units, configured per-product from a metabox on the product edit screen.
- **Role-based pricing**: wholesale customers (or any role you configure) get a flat percentage off store-wide, stacked on top of quantity breaks.
- **Price display is accurate everywhere it matters**: shop loop, product page, and cart — not just the final total, which is the usual bug in hand-rolled pricing hacks.

## Why it's built this way

- Hooks into `woocommerce_get_price` / `woocommerce_product_get_price` rather than filtering the cart total after the fact — so the discounted price is what the customer sees before they even add to cart, not a surprise at checkout.
- Pricing rules are stored as product meta (`_dynamic_pricing_tiers`), not in a separate custom table — keeps the plugin dependency-free and the data portable if the store migrates.
- Role discount is a single filterable option (`dynamic_pricing_role_discount_percent`) so a theme or another plugin can override it per-site without forking this one.
- No admin-ajax spaghetti: the settings UI is a plain metabox with a nonce-verified save, following the same pattern WooCommerce core uses for its own product data tabs.

## Structure

```
woocommerce-dynamic-pricing.php   # Plugin bootstrap, header, activation checks
includes/
  class-dynamic-pricing.php       # Price calculation + hooks into WC's price filters
  class-admin-settings.php        # Product-edit metabox for configuring tiers
assets/
  admin.js                        # Repeatable tier rows in the metabox UI
uninstall.php                     # Cleans up plugin meta on uninstall
```

## Installation

1. Copy this folder into `wp-content/plugins/woocommerce-dynamic-pricing`.
2. Activate it from the WordPress admin (requires WooCommerce to be active).
3. On any product's edit screen, open the **Dynamic Pricing** tab to set quantity tiers.
4. Set the wholesale role discount under **WooCommerce → Settings → Products → Dynamic Pricing**.

## Example

Product priced at $50, tiers set to 5+ → 10% off, 20+ → 20% off, and the customer is in the `wholesale_customer` role with a 15% store-wide discount configured:

- 1-4 units: role discount only → $42.50/unit
- 5-19 units: quantity tier (10%) + role discount (15%), applied multiplicatively → $38.25/unit
- 20+ units: quantity tier (20%) + role discount (15%) → $34.00/unit

---
Built by [Aqib Shafique](https://aqib.net) — WordPress/WooCommerce, PHP/Laravel, AI automation.
