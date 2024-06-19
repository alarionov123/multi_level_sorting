<?php

use Tygh\Registry;

/**
 * Products sorting hook handler
 * Adds new sorting
 *
 * @param $sorting
 * @return void
 *
 * @see fn_get_products_sorting
 */
function fn_multi_level_sorting_products_sorting(&$sorting)
{
    $sorting['default_sorting'] = [
        'description' => __('default_sorting'),
        'default_order' => 'desc',
        'asc' => false,
    ];
}

/**
 * Get products hook handler
 * Adds a multi-level sorting in a product listing pages
 * Firstly goes products with a particular feature enabled, then in-stock products, then out of stock and products.out_of_stock_actions = 'R' and then the rest out of stock products
 *
 * @param $params
 * @param $fields
 * @param $sortings
 * @param $condition
 * @param $join
 * @param $sorting
 * @param $group_by
 * @param $lang_code
 * @param $having
 * @return void
 *
 * @see fn_get_products
 */
function fn_multi_level_sorting_get_products(
    $params,
    &$fields,
    &$sortings,
    $condition,
    &$join,
    &$sorting,
    $group_by,
    $lang_code,
    $having
) {
    $sortings['default_sorting'] = 'product';
    $fields[] = 'products.out_of_stock_actions';
    $feature_variants = explode(',', Registry::get('addons.multi_level_sorting.feature_ids_to_be_sorted_first'));

    if (Registry::get('addons.warehouses.status') === 'A') {
        $sorting = db_quote(
            " ORDER BY
    CASE 
        WHEN EXISTS (SELECT variant_id FROM cscart_product_features_values WHERE product_id = products.product_id AND feature_id IN (?n)) AND war_sum_amount.amount > 0 THEN 1
        WHEN war_sum_amount.amount > 0 THEN 2 
        WHEN products.out_of_stock_actions = 'R' AND war_sum_amount.amount <= 0 THEN 3
        ELSE 4
    END,
    product ASC",
            $feature_variants
        );
    } else {
        $fields[] = 'products.amount';
        $sorting = db_quote(
            " ORDER BY
    CASE
    WHEN EXISTS (SELECT variant_id FROM cscart_product_features_values WHERE product_id = products.product_id AND feature_id IN (?n)) AND war_sum_amount.amount > 0 THEN 0
        WHEN products.amount > 0 THEN 1
        WHEN products.out_of_stock_actions = 'R' AND war_sum_amount.amount <= 0 THEN 3
        WHEN products.amount <= 0 THEN 3
        ELSE 4
    END,
    product ASC",
            $feature_variants
        );
    }
}