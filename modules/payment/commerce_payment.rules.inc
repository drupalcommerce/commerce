<?php
// $Id$

/**
 * @file
 * Rules integration for payment.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_payment_rules_event_info() {
  $items = array(
    'commerce_payment_methods' => array(
      'label' => t('Select available payment methods for an order'),
      'group' => t('Commerce - payment'),
      'variables' => commerce_order_rules_variables(t('order')),
      'access callback' => 'commerce_order_rules_access',
    ),
  );

  return $items;
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_payment_rules_action_info() {
  // Add an action for each available payment method.
  foreach (commerce_payment_methods() as $method) {
    $actions['commerce_payment_enable_' . $method['method_id']] = array(
      'label' => t('Enable payment method: @method', array('@method' => $method['title'])),
      'parameter' => array(
        'commerce_order' => array('type' => 'commerce_order', 'label' => t('Order')),
        'payment_method' => array(
          'type' => 'commerce_payment_settings',
          'restriction' => 'input',
          'label' => t('Payment settings'),
          'payment_method' => $method['method_id'],
        ),
      ),
      'group' => t('Drupal Commerce - payment'),
      'base' => $method['base'],
      'callbacks' => array(
        'execute' => 'commerce_payment_enable_method',
      ),
    );
  }

  $actions['commerce_payment_capture'] = array(
    'label' => t('Capture from a previous authorization'),
    'parameter' => array(
      'commerce_order' => array('type' => 'commerce_order', 'label' => t('Order')),
      'amount' => array('type' => 'text', 'label' => t('Amount')),
    ),
    'group' => t('Drupal Commerce - payment'),
  );

  return $actions;
}

/**
 * Generic execution callback for the payment method.
 */
function commerce_payment_enable_method($order, $payment_method) {
  $order->payment_methods[] = $payment_method;
}

/**
 * Rules action: capture a payment from a previous authorization.
 */
function commerce_payment_capture($order, $amount) {

}

/**
 * Implements hook_rules_data_info().
 */
function commerce_payment_rules_data_info() {
  $data['commerce_payment_settings'] = array(
    'label' => t('payment settings'),
    'ui class' => 'RulesDataUIPaymentSettings',
  );
  return $data;
}

/**
 * UI for textual data.
 */
class RulesDataUIPaymentSettings extends RulesDataUI implements RulesDataDirectInputForm {
  public static function getDefaultMode() {
    return 'input';
  }

  public static function inputForm($name, $info, $settings, RulesPlugin $element) {
    $payment_method = commerce_payment_method($info['payment_method']);
    if ($payment_method) {
      $form[$name]['method_id'] = array('#type' => 'value', '#value' => $info['payment_method']);
      if (function_exists($payment_method['callbacks']['settings'])) {
        $form[$name]['settings'] = $payment_method['callbacks']['settings']($settings[$name]['settings']);
      }
      else {
        $form[$name]['settings']['no_settings']['#markup'] = t('No settings for this payment method.');
      }
    }
    else {
      $form[$name]['invalid']['#markup'] = t('Invalid payment method.');
    }
    return $form;
  }

  public static function render($value) {
    return array();
  }
}

/**
 * @}
 */
