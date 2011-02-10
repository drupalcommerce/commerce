<?php
// $Id$

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
  $events = array();

  $events['commerce_payment_methods'] = array(
    'label' => t('Select available payment methods for an order'),
    'group' => t('Commerce Payment'),
    'variables' => commerce_order_rules_event_variables(t('Order', array(), array('context' => 'a drupal commerce order'))),
    'access callback' => 'commerce_order_rules_access',
  );
  $events['commerce_payment_transaction_presave'] = array(
    'label' => t('Before saving a payment transaction'),
    'group' => t('Commerce Payment'),
    'variables' => commerce_payment_rules_event_variables(t('Payment transaction'), TRUE, TRUE),
    'access callback' => 'commerce_payment_rules_access',
  );
  $events['commerce_payment_transaction_insert'] = array(
    'label' => t('After saving a new payment transaction'),
    'group' => t('Commerce Payment'),
    'variables' => commerce_payment_rules_event_variables(t('Created payment transaction'), TRUE),
    'access callback' => 'commerce_payment_rules_access',
  );
  $events['commerce_payment_transaction_update'] = array(
    'label' => t('After updating an existing payment transaction'),
    'group' => t('Commerce Payment'),
    'variables' => commerce_payment_rules_event_variables(t('Updated payment transaction'), TRUE),
    'access callback' => 'commerce_payment_rules_access',
  );
  $events['commerce_payment_transaction_delete'] = array(
    'label' => t('After deleting a payment transaction'),
    'group' => t('Commerce Payment'),
    'variables' => commerce_payment_rules_event_variables(t('Deleted payment transaction')),
    'access callback' => 'commerce_payment_rules_access',
  );

  return $events;
}

/**
 * Returns a variables array for payment transaction events.
 *
 * @param $label
 *   The label for the primary payment transaction variable.
 * @param $unchanged
 *   Boolean indicating whether or not to include the unchanged transaction.
 * @param $skip_save
 *   Boolean indicating whether or not the primary transaction variable should
 *     skip saving after event execution.
 */
function commerce_payment_rules_event_variables($label, $unchanged = FALSE, $skip_save = FALSE) {
  $variables = array(
    'payment_transaction' => array(
      'type' => 'commerce_payment_transaction',
      'label' => $label,
      'skip save' => $skip_save,
    ),
  );

  if ($unchanged) {
    $variables['payment_transaction_unchanged'] = array(
      'type' => 'commerce_payment_transaction',
      'label' => t('Unchanged payment transaction'),
      'skip save' => TRUE,
      'handler' => 'rules_events_entity_unchanged',
    );
  }

  return $variables;
}

/**
 * Rules integration access callback.
 */
function commerce_payment_rules_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return TRUE;
  }
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
        'commerce_order' => array('type' => 'commerce_order', 'label' => t('Order', array(), array('context' => 'a drupal commerce order'))),
        'payment_method' => array(
          'type' => 'commerce_payment_settings',
          'restriction' => 'input',
          'label' => t('Payment settings'),
          'payment_method' => $payment_method['method_id'],
        ),
      ),
      'group' => t('Commerce Payment'),
      'base' => $payment_method['base'],
      'callbacks' => array(
        'execute' => 'commerce_payment_enable_method',
      ),
    );
  }

  $actions['commerce_payment_capture'] = array(
    'label' => t('Capture from a prior authorization'),
    'parameter' => array(
      'commerce_order' => array('type' => 'commerce_order', 'label' => t('Order', array(), array('context' => 'a drupal commerce order'))),
      'amount' => array('type' => 'text', 'label' => t('Amount')),
    ),
    'group' => t('Commerce Payment'),
  );

  return $actions;
}

/**
 * Generic execution callback for the payment method.
 */
function commerce_payment_enable_method($order, $payment_method, $action_settings, $rule_state, $action, $callback_type) {
  // Find the Rule that contains this action.
  $rule = $action->parentElement();

  while ($rule->getPluginName() != 'reaction rule') {
    $rule = $rule->parentElement();
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
 * Rules action: capture a payment from a previous authorization.
 */
function commerce_payment_capture($order, $amount) {

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
    if ($payment_method = commerce_payment_method_load($info['payment_method'])) {
      $form[$name]['method_id'] = array('#type' => 'value', '#value' => $info['payment_method']);

      // If the payment method has a settings callback...
      if ($callback = commerce_payment_method_callback($payment_method, 'settings_form')) {
        // Prepare an array of payment method settings defaults.
        $method_settings = !empty($settings[$name]) ? $settings[$name]['settings'] : array();

        // Add the settings form elements to the action form.
        $form[$name]['settings'] = $callback($method_settings);
      }
      else {
        // Otherwise add an appropriate message.
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
