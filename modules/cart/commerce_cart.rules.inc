<?php
// $Id$

/**
 * @file
 * Rules integration for shopping carts.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_cart_rules_event_info() {
  $events = array();

  $events['commerce_cart_product_prepare'] = array(
    'label' => t('Before adding a product to the cart'),
    'group' => t('Commerce Cart'),
    'variables' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Shopping cart order'),
      ),
      'product' => array(
        'label' => t('Product'),
        'type' => 'commerce_product',
      ),
      'quantity' => array(
        'label' => t('Quantity'),
        'type' => 'integer',
      ),
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  $events['commerce_cart_product_add'] = array(
    'label' => t('After adding a product to the cart'),
    'group' => t('Commerce Cart'),
    'variables' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Shopping cart order'),
      ),
      'product' => array(
        'label' => t('Product'),
        'type' => 'commerce_product',
      ),
      'quantity' => array(
        'label' => t('Quantity'),
        'type' => 'integer',
      ),
      'line_item' => array(
        'label' => t('Product line item'),
        'type' => 'commerce_line_item',
      ),
      'line_item_unchanged' => array(
        'label' => t('Unchanged product line item'),
        'type' => 'commerce_line_item',
        'skip save' => TRUE,
        'handler' => 'rules_events_entity_unchanged',
      ),
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * @}
 */
