<?php
/**
 * Plugin Name: WooCommerce Dynamic Pricing
 * Plugin URI: https://aqib.net
 * Description: Quantity-tier and customer-role pricing for WooCommerce, applied directly at the price-filter level so discounts show correctly everywhere — shop loop, product page, and cart.
 * Version: 1.0.0
 * Author: Aqib Shafique
 * Author URI: https://aqib.net
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-dynamic-pricing
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.3
 */

if (! defined('ABSPATH')) {
    exit; // No direct access.
}

define('WC_DYNAMIC_PRICING_VERSION', '1.0.0');
define('WC_DYNAMIC_PRICING_PATH', plugin_dir_path(__FILE__));

/**
 * Bail cleanly (with an admin notice) if WooCommerce isn't active, rather
 * than fataling on an undefined WC_Product class.
 */
function wc_dynamic_pricing_check_woocommerce()
{
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('WooCommerce Dynamic Pricing requires WooCommerce to be installed and active.', 'wc-dynamic-pricing');
            echo '</p></div>';
        });

        return false;
    }

    return true;
}

add_action('plugins_loaded', function () {
    if (! wc_dynamic_pricing_check_woocommerce()) {
        return;
    }

    require_once WC_DYNAMIC_PRICING_PATH . 'includes/class-dynamic-pricing.php';
    require_once WC_DYNAMIC_PRICING_PATH . 'includes/class-admin-settings.php';

    new WC_Dynamic_Pricing();
    new WC_Dynamic_Pricing_Admin_Settings();
});
