<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Applies quantity-tier and role-based discounts by filtering the product
 * price directly, so the discount is visible on the shop loop and product
 * page — not just computed silently at cart total time.
 */
class WC_Dynamic_Pricing
{
    const META_KEY = '_dynamic_pricing_tiers';

    public function __construct()
    {
        // Product page / shop loop display price.
        add_filter('woocommerce_product_get_price', [$this, 'filter_display_price'], 10, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'filter_display_price'], 10, 2);

        // Cart line price, where quantity is actually known.
        add_action('woocommerce_before_calculate_totals', [$this, 'apply_cart_pricing'], 20, 1);
    }

    /**
     * Storefront display price (no quantity context yet, so this reflects
     * the role discount and the *best available* quantity tier as a
     * "starting from" price — the cart applies the exact tier once quantity
     * is known).
     */
    public function filter_display_price($price, $product)
    {
        if ('' === $price || null === $price) {
            return $price;
        }

        $price = (float) $price;
        $price = $this->apply_role_discount($price);

        $best_tier_percent = $this->best_available_tier_percent($product->get_id());
        if ($best_tier_percent > 0) {
            $price = $this->apply_percent_discount($price, $best_tier_percent);
        }

        return $price;
    }

    /**
     * Cart line pricing: quantity is known here, so we apply the exact tier
     * that matches, stacked with the role discount.
     */
    public function apply_cart_pricing($cart)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }

        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return; // avoid double-application on repeated hook calls
        }

        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];

            $base_price = (float) $product->get_regular_price();
            if ($base_price <= 0) {
                continue;
            }

            $price = $this->apply_role_discount($base_price);

            $tier_percent = $this->tier_percent_for_quantity($product->get_id(), $quantity);
            if ($tier_percent > 0) {
                $price = $this->apply_percent_discount($price, $tier_percent);
            }

            $product->set_price($price);
        }
    }

    protected function apply_role_discount(float $price): float
    {
        $percent = $this->current_user_role_discount_percent();

        return $percent > 0 ? $this->apply_percent_discount($price, $percent) : $price;
    }

    /**
     * The role discount is filterable so a theme/another plugin can
     * override per-site without forking this plugin.
     */
    protected function current_user_role_discount_percent(): float
    {
        $configured_role = get_option('wc_dynamic_pricing_discount_role', '');
        $configured_percent = (float) get_option('wc_dynamic_pricing_discount_percent', 0);

        if (! $configured_role || $configured_percent <= 0 || ! is_user_logged_in()) {
            return 0.0;
        }

        $user = wp_get_current_user();

        $percent = in_array($configured_role, (array) $user->roles, true) ? $configured_percent : 0.0;

        return (float) apply_filters('dynamic_pricing_role_discount_percent', $percent, $user);
    }

    protected function apply_percent_discount(float $price, float $percent): float
    {
        $percent = max(0, min(100, $percent));

        return round($price * (1 - ($percent / 100)), 2);
    }

    protected function get_tiers(int $product_id): array
    {
        $tiers = get_post_meta($product_id, self::META_KEY, true);

        if (! is_array($tiers)) {
            return [];
        }

        // Normalize + sort ascending by min_qty so lookups are predictable.
        $tiers = array_filter($tiers, fn ($t) => isset($t['min_qty'], $t['percent']));
        usort($tiers, fn ($a, $b) => $a['min_qty'] <=> $b['min_qty']);

        return $tiers;
    }

    protected function tier_percent_for_quantity(int $product_id, int $quantity): float
    {
        $matched = 0.0;

        foreach ($this->get_tiers($product_id) as $tier) {
            if ($quantity >= (int) $tier['min_qty']) {
                $matched = (float) $tier['percent']; // tiers are sorted ascending, so last match wins
            }
        }

        return $matched;
    }

    protected function best_available_tier_percent(int $product_id): float
    {
        $tiers = $this->get_tiers($product_id);

        if (empty($tiers)) {
            return 0.0;
        }

        return (float) end($tiers)['percent'];
    }
}
