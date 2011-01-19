<?php
// $Id$

/**
 * @file
 * Rules integration for the checkout process.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_checkout_rules_event_info() {
  $events = array();

  $events['commerce_checkout_complete'] = array(
    'label' => t('Completing the checkout process'),
    'group' => t('Commerce Checkout'),
    'variables' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Completed order', array(), array('context' => 'a drupal commerce order')),
      ),
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * @}
 */
