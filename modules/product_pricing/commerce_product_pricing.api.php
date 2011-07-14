<?php

/**
 * @file
 * Hooks provided by the Product Pricing module.
 */


/**
 * Lets modules invalidate a particular product during the sell price pre-
 *   calculation process.
 *
 * Because the price table can very quickly accumulate millions of rows on
 * complex websites, it is advantageous to prevent any unnecessary products from
 * cluttering up the table. This hook allows modules to prevent pre-calculation
 * on an individual product, which is especially useful when it is known that
 * products meeting certain criteria will never be featured in Views and other
 * displays where it might be sorted or filtered based on a calculated price.
 *
 * @param $product
 *   The product being considered for sell price pre-calculation.
 *
 * @return
 *   TRUE or FALSE indicating whether or not the product is valid.
 *
 * @see hook_commerce_product_valid_pre_calculation_rule()
 */
function hook_commerce_product_valid_pre_calculation_product($product) {
  // Disable sell price pre-calculation for inactive products.
  if (!$product->status) {
    return FALSE;
  }
}

/**
 * Lets modules invalidate a particular rule configuration during the sell price
 *   pre-calculation process.
 *
 * Because the price table can very quickly accumulate millions of rows on
 * complex websites, it is advantageous to prevent any unnecessary rule
 * configurations from the pre-calculation process. Each additional rule
 * configuration exponentially increases the amount of rows necessary for each
 * product whose sell price is pre-calculated.
 *
 * This hook allows modules to prevent pre-calculation for individual rule
 * configurations, which is especially useful when it is known that certain
 * rule configurations will never affect the prices of products featured in
 * Views or other displays that sort or filter based on a calculated price.
 *
 * @param $rule
 *   A rule configuration belonging to the commerce_product_calculate_sell_price
 *     event.
 *
 * @return
 *   TRUE or FALSE indicating whether or not the rule configuration is valid.
 *
 * @see hook_commerce_product_valid_pre_calculation_product()
 */
function hook_commerce_product_valid_pre_calculation_rule($rule) {
  // TODO: Use the implementation specced in http://drupal.org/node/1020976 as
  // an example here.
}

/**
 * Allows modules to alter the product line item used for sell price calculation.
 *
 * @param $line_item
 *   The product line item used for sell price calculation.
 */
function hook_commerce_product_calculate_sell_price_line_item_alter($line_item) {
  global $user;

  // Reference the current shopping cart order in the line item if it isn't set.
  if (empty($line_item->order_id)) {
    $line_item->order_id = commerce_cart_order_id($user->uid);
  }
}
