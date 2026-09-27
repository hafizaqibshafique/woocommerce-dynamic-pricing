<?php

// Fires only on "Delete", never on plain deactivation — so the pricing
// config survives a deactivate/reactivate cycle but is actually cleaned up
// if the site owner removes the plugin for good.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Remove per-product tier meta.
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        '_dynamic_pricing_tiers'
    )
);

// Remove plugin-wide settings.
delete_option('wc_dynamic_pricing_discount_role');
delete_option('wc_dynamic_pricing_discount_percent');
