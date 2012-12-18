<?php

/**
 * @file
 * Rules integration for payments.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_payment_rules_event_info() {
  // So that we can use the entity_rules_events_variables() helper function.
  module_load_include('inc', 'entity', 'entity.rules');

  $events = array();

  $events['commerce_payment_methods'] = array(
    'label' => t('Select available payment methods for an order'),
    'group' => t('Commerce Payment'),
    'variables' => entity_rules_events_variables('commerce_order', t('Order', array(), array('context' => 'a drupal commerce order'))),
    'access callback' => 'commerce_order_rules_access',
  );

  $variables = array_merge(
    entity_rules_events_variables('commerce_order', t('Order', array(), array('context' => 'a drupal commerce order')), TRUE, TRUE),
    entity_rules_events_variables('commerce_payment_transaction', t('Last completed transaction'), TRUE)
  );

  $events['commerce_payment_order_paid_in_full'] = array(
    'label' => t('When an order is first paid in full'),
    'group' => t('Commerce Payment'),
    'variables' => $variables,
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

/**
 * Implements hook_rules_condition_info().
 */
function commerce_payment_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_payment_order_balance_comparison'] = array(
    'label' => t('Order balance comparison'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order whose balance should be compared (calculated as the order total minus completed payment amounts).'),
      ),
      'operator' => array(
        'type' => 'text',
        'label' => t('Operator'),
        'description' => t('The comparison operator.'),
        'optional' => TRUE,
        'default value' => '<=',
        'options list' => 'commerce_numeric_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'value' => array(
        'type' => 'text',
        'label' => t('Value'),
        'description' => t('Integer representing a value in minor currency units to compare against, such as 1000 for $10. A balance of 0 or less indicates the order has been paid in full.'),
        'default value' => '0',
      ),
    ),
    'group' => t('Commerce Payment'),
    'callbacks' => array(
      'execute' => 'commerce_payment_rules_compare_balance',
    ),
  );

  $conditions['commerce_payment_selected_payment_method'] = array(
    'label' => t('Selected payment method comparison'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order whose selected payment method (if any) should be compared against the method specified below.'),
      ),
      'method_id' => array(
        'type' => 'text',
        'label' => t('Payment method'),
        'description' => t('This condition will perform a simple equivalency check to see if the payment method you specify matches what a customer selected on the checkout form.'),
        'options list' => 'commerce_payment_rules_payment_method_options_list',
        'restriction' => 'input',
      ),
    ),
    'group' => t('Commerce Payment'),
    'callbacks' => array(
      'execute' => 'commerce_payment_rules_compare_selected_payment_method',
    ),
  );

  return $conditions;
}

/**
 * Condition callback: checks the unpaid balance of an order.
 */
function commerce_payment_rules_compare_balance($order, $operator, $value) {
  // Check the balance of the order.
  $balance = commerce_payment_order_balance($order);

  // If the balance was incalculable, set the balance to the order total.
  if ($balance === FALSE) {
    $balance = entity_metadata_wrapper('commerce_order', $order)->commerce_order_total->value();
  }

  // Make a quantity comparison based on the operator.
  switch ($operator) {
    case '<':
      return $balance['amount'] < $value;
    case '<=':
      return $balance['amount'] <= $value;
    case '=':
      return $balance['amount'] == $value;
    case '>=':
      return $balance['amount'] >= $value;
    case '>':
      return $balance['amount'] > $value;
  }

  return FALSE;
}

/**
 * Returns an options list of available payment methods including a None option.
 */
function commerce_payment_rules_payment_method_options_list() {
  return array('-none-' => t('None')) + commerce_payment_method_get_title();
}

/**
 * Condition callback: compares the selected payment method for an order.
 */
function commerce_payment_rules_compare_selected_payment_method($order, $method_id) {
  if (!empty($order->data['payment_method'])) {
    list($selected_method_id, ) = explode('|', $order->data['payment_method']);
  }
  else {
    $selected_method_id = '-none-';
  }

  return $method_id == $selected_method_id;
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_payment_rules_action_info() {
  $actions = array();

  // Add an action for each available payment method.
  foreach (commerce_payment_methods() as $payment_method) {
    $actions['commerce_payment_enable_' . $payment_method['method_id']] = array(
      'label' => t('Enable payment method: @method', array('@method' => $payment_method['title'])),
      'parameter' => array(
        'commerce_order' => array(
          'type' => 'commerce_order',
          'label' => t('Order', array(), array('context' => 'a drupal commerce order')),
          'skip save' => TRUE,
        ),
        'payment_method' => array(
          'type' => 'commerce_payment_settings',
          'restriction' => 'input',
          'label' => t('Payment settings'),
          'payment_method' => $payment_method['method_id'],
        ),
      ),
      'group' => t('Commerce Payment'),
      'callbacks' => array(
        'execute' => 'commerce_payment_enable_method',
      ),
    );
  }

  return $actions;
}

/**
 * Generic execution callback for the payment method.
 */
function commerce_payment_enable_method($order, $payment_method, $action_settings, $rule_state, $action, $callback_type) {
  // Find the Rule that contains this action.
  $rule = $action->parentElement();

  while (!($rule instanceof RulesActionContainer)) {
    if ($rule->parentElement()) {
      $rule = $rule->parentElement();
    }
    else {
      return;
    }
  }

  // Initialize variables for the payment method ID and settings.
  if (is_array($payment_method)) {
    $method_id = $payment_method['method_id'];
    $settings = !empty($payment_method['settings']) ? $payment_method['settings'] : array();
  }
  else {
    $method_id = $payment_method;
    $settings = array();
  }

  // Create a unique key for the instance of the payment method represented by
  // this action.
  $instance_id = commerce_payment_method_instance_id($method_id, $rule);

  // Set the payment method to the order along with its settings and context.
  $order->payment_methods[$instance_id] = array(
    'method_id' => $method_id,
    'settings' => $settings,
    'rule_name' => $rule->name,
    'weight' => $rule->weight,
  );
}

/**
 * Implements hook_rules_data_info().
 */
function commerce_payment_rules_data_info() {
  $data['commerce_payment_settings'] = array(
    'label' => t('Payment settings'),
    'ui class' => 'RulesDataUIPaymentSettings',
  );
  return $data;
}

/**
 * Adds a payment method settings form to the enabling action.
 */
class RulesDataUIPaymentSettings extends RulesDataUI implements RulesDataDirectInputFormInterface {
  public static function getDefaultMode() {
    return 'input';
  }

  public static function inputForm($name, $info, $settings, RulesPlugin $element) {
    // If the specified payment method exists...
    if (!empty($info['payment_method']) && $payment_method = commerce_payment_method_load($info['payment_method'])) {
      $form[$name]['method_id'] = array('#type' => 'value', '#value' => $info['payment_method']);

      // If the payment method has a settings callback...
      if ($callback = commerce_payment_method_callback($payment_method, 'settings_form')) {
        // Prepare an array of payment method settings defaults.
        $method_settings = !empty($settings[$name]['settings']) && is_array($settings[$name]['settings']) ? $settings[$name]['settings'] : array();

        // Add the settings form elements to the action form.
        $form[$name]['settings'] = $callback($method_settings);
      }
      else {
        // Otherwise add an appropriate message.
        $form[$name]['settings']['no_settings']['#markup'] = t('No settings for this payment method.');
      }
    }
    else {
      $form[$name]['invalid']['#markup'] = t('Invalid or missing payment method.');
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
