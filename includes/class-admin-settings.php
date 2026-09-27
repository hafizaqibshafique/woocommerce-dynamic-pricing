<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Adds a "Dynamic Pricing" product data tab for quantity tiers, and a
 * settings section under WooCommerce > Settings > Products for the
 * store-wide role discount.
 */
class WC_Dynamic_Pricing_Admin_Settings
{
    public function __construct()
    {
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'render_product_data_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_tiers']);

        add_filter('woocommerce_get_sections_products', [$this, 'add_settings_section']);
        add_filter('woocommerce_get_settings_products', [$this, 'add_settings_fields'], 10, 2);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_product_data_tab($tabs)
    {
        $tabs['dynamic_pricing'] = [
            'label' => __('Dynamic Pricing', 'wc-dynamic-pricing'),
            'target' => 'dynamic_pricing_product_data',
            'class' => ['show_if_simple', 'show_if_variable'],
            'priority' => 21,
        ];

        return $tabs;
    }

    public function render_product_data_panel()
    {
        global $post;

        $tiers = get_post_meta($post->ID, WC_Dynamic_Pricing::META_KEY, true);
        $tiers = is_array($tiers) ? $tiers : [];

        wp_nonce_field('wc_dynamic_pricing_save', 'wc_dynamic_pricing_nonce');
        ?>
        <div id="dynamic_pricing_product_data" class="panel woocommerce_options_panel">
            <p class="form-field">
                <label><?php esc_html_e('Quantity discount tiers', 'wc-dynamic-pricing'); ?></label>
            </p>
            <table class="widefat wc-dynamic-pricing-tiers" style="max-width:480px;margin-left:12px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Min. quantity', 'wc-dynamic-pricing'); ?></th>
                        <th><?php esc_html_e('Discount %', 'wc-dynamic-pricing'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="tier-rows">
                    <?php foreach ($tiers as $tier) : ?>
                        <tr>
                            <td><input type="number" min="1" name="dynamic_pricing_min_qty[]" value="<?php echo esc_attr($tier['min_qty'] ?? ''); ?>" /></td>
                            <td><input type="number" min="0" max="100" step="0.01" name="dynamic_pricing_percent[]" value="<?php echo esc_attr($tier['percent'] ?? ''); ?>" /></td>
                            <td><button type="button" class="button remove-tier-row">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button add-tier-row"><?php esc_html_e('+ Add tier', 'wc-dynamic-pricing'); ?></button></p>
        </div>
        <?php
    }

    public function save_product_tiers($product_id)
    {
        if (! isset($_POST['wc_dynamic_pricing_nonce']) ||
            ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wc_dynamic_pricing_nonce'])), 'wc_dynamic_pricing_save')) {
            return;
        }

        if (! current_user_can('edit_product', $product_id)) {
            return;
        }

        $min_qtys = isset($_POST['dynamic_pricing_min_qty']) ? array_map('absint', wp_unslash($_POST['dynamic_pricing_min_qty'])) : [];
        $percents = isset($_POST['dynamic_pricing_percent']) ? array_map('floatval', wp_unslash($_POST['dynamic_pricing_percent'])) : [];

        $tiers = [];
        foreach ($min_qtys as $index => $min_qty) {
            $percent = $percents[$index] ?? 0;
            if ($min_qty > 0 && $percent > 0) {
                $tiers[] = ['min_qty' => $min_qty, 'percent' => $percent];
            }
        }

        update_post_meta($product_id, WC_Dynamic_Pricing::META_KEY, $tiers);
    }

    public function add_settings_section($sections)
    {
        $sections['dynamic_pricing'] = __('Dynamic Pricing', 'wc-dynamic-pricing');

        return $sections;
    }

    public function add_settings_fields($settings, $current_section)
    {
        if ('dynamic_pricing' !== $current_section) {
            return $settings;
        }

        return [
            [
                'title' => __('Role-based discount', 'wc-dynamic-pricing'),
                'type' => 'title',
                'id' => 'wc_dynamic_pricing_role_section',
            ],
            [
                'title' => __('Discounted role', 'wc-dynamic-pricing'),
                'desc' => __('Customers in this role get a store-wide discount, stacked with any quantity tier.', 'wc-dynamic-pricing'),
                'id' => 'wc_dynamic_pricing_discount_role',
                'type' => 'select',
                'options' => wp_roles()->get_names(),
                'default' => '',
            ],
            [
                'title' => __('Discount percent', 'wc-dynamic-pricing'),
                'id' => 'wc_dynamic_pricing_discount_percent',
                'type' => 'number',
                'default' => '0',
                'custom_attributes' => ['min' => 0, 'max' => 100, 'step' => 0.01],
            ],
            [
                'type' => 'sectionend',
                'id' => 'wc_dynamic_pricing_role_section',
            ],
        ];
    }

    public function enqueue_admin_assets($hook)
    {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        if (! $post || 'product' !== $post->post_type) {
            return;
        }

        wp_enqueue_script(
            'wc-dynamic-pricing-admin',
            plugins_url('assets/admin.js', dirname(__FILE__)),
            ['jquery'],
            WC_DYNAMIC_PRICING_VERSION,
            true
        );
    }
}
