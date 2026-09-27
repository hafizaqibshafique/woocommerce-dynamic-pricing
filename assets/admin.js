/**
 * Adds/removes repeatable quantity-tier rows in the product edit metabox.
 * Plain jQuery to match WooCommerce core's own admin scripts — no build
 * step needed for a UI this small.
 */
jQuery(function ($) {
    $('#dynamic_pricing_product_data').on('click', '.add-tier-row', function (e) {
        e.preventDefault();

        var $table = $(this).closest('#dynamic_pricing_product_data').find('.wc-dynamic-pricing-tiers tbody');
        var $row = $(
            '<tr>' +
                '<td><input type="number" min="1" name="dynamic_pricing_min_qty[]" /></td>' +
                '<td><input type="number" min="0" max="100" step="0.01" name="dynamic_pricing_percent[]" /></td>' +
                '<td><button type="button" class="button remove-tier-row">&times;</button></td>' +
            '</tr>'
        );

        $table.append($row);
    });

    $(document).on('click', '.remove-tier-row', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });
});
