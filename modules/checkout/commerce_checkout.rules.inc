<?php
// $Id$

/**
 * @file
 * Rules integration for checkout.
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
    'label' => t('Completion of the checkout flow'),
    'group' => t('Commerce - checkout'),
    'variables' => array(
      'order' => array(
        'type' => 'commerce_order',
        'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
        'skip save' => FALSE,
      ),
      'account' => array('type' => 'user', 'label' => t('User completed checkout'))
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * @}
 */
