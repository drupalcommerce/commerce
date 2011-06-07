<?php

/**
 * @file
 * Rules integration for the Product Reference module.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 *
 * Defines the event used to calculate the sell price of a product. This event
 * is governed by this module since it defines the product line item type used
 * as a variable and also defines the field used to display products to users
 * for purchase.
 */
function commerce_product_reference_rules_event_info() {
  $items = array();

  $items['commerce_product_calculate_sell_price'] = array(
    'label' => t('Calculating the sell price of a product'),
    'group' => t('Commerce Product'),
    'variables' => array(
      'commerce_line_item' => array(
        'label' => t('Product line item'),
        'type' => 'commerce_line_item',
        'skip save' => TRUE,
      ),
      'commerce_line_item_unchanged' => array(
        'label' => t('Unchanged product line item'),
        'type' => 'commerce_line_item',
        'skip save' => TRUE,
        'handler' => 'rules_events_entity_unchanged',
      ),
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  return $items;
}

/**
 * @}
 */
