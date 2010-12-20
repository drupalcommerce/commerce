<?php
// $Id$

/**
 * @file
 * Rules integration for the Price module.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Returns parameters suitable for using Rules to alter prices.
 */
function commerce_price_rules_variables() {
  $args = array(
    'price' => array(
      'type' => 'commerce_price',
      'label' => t('Price'),
    ),
  );

  return $args;
}

/**
 * Implements hook_rules_data_info().
 */
function commerce_price_rules_data_info() {
  return array(
    'commerce_price' => array(
      'label' => t('price'),
      'ui class' => 'RulesDataUICommercePrice',
      'wrap' => TRUE,
      'property info' => commerce_price_field_data_property_info(),
    ),
  );
}

/**
 * Defines a commerce_price input form for Rules actions altering price fields.
 */
class RulesDataUICommercePrice extends RulesDataUI implements RulesDataDirectInputFormInterface {
  public static function getDefaultMode() {
    return 'input';
  }

  public static function inputForm($name, $info, $settings, RulesPlugin $element) {
    // Use the default currency if the setting is not present.
    if (empty($settings[$name]['currency_code']) || $settings[$name]['currency_code'] == 'default') {
      $default_currency_code = NULL;
    }
    else {
      $default_currency_code = $settings[$name]['currency_code'];
    }

    // If a price has already been set for this instance prepare default values.
    if (isset($settings[$name]['amount'])) {
      $currency = commerce_currency_load($settings[$name]['currency_code']);

      // Round the default value.
      $default_amount = round($settings[$name]['amount'], 2);

      // Run it through number_format() to add the decimal places in if necessary.
      if (strpos($default_amount, '.') === FALSE || strpos($default_amount, '.') > strlen($default_amount) - $currency->decimals) {
        $default_amount = number_format($default_amount, $currency->decimals, '.', '');
      }

      $default_currency_code = $settings[$name]['currency_code'];
    }
    else {
      $default_amount = NULL;
    }

    // Load the default currency for this instance.
    $default_currency = commerce_currency_load($default_currency_code);

    $form[$name]['#attached']['css'][] = drupal_get_path('module', 'commerce_price') . '/theme/commerce_price.css';

    $form[$name]['amount'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_amount,
      '#size' => 10,
    );

    // Build a currency options list from all enabled currencies.
    $options = array();

    foreach (commerce_currencies(TRUE) as $currency_code => $currency) {
      $options[$currency_code] = check_plain($currency->code);
    }

    // If the current currency value is not available, add it now with a
    // message in the help text explaining it.
    if (empty($options[$default_currency->code])) {
      $options[$default_currency->code] = check_plain($default_currency->code);

      $description = t('The currency set for this action is not currently enabled. If you change it now, you will not be able to set it back.');
    }
    else {
      $description = '';
    }

    // If only one currency option is available, don't use a select list.
    if (count($options) == 1) {
      $currency_code = array_shift(array_keys($options));

      $form[$name]['amount']['#field_suffix'] = $currency_code;

      $form[$name]['currency_code'] = array(
        '#type' => 'value',
        '#default_value' => $currency_code,
      );
    }
    else {
      $form[$name]['amount']['#prefix'] = '<div class="commerce-price-full">';

      $form[$name]['currency_code'] = array(
        '#type' => 'select',
        '#description' => $description,
        '#options' => $options,
        '#default_value' => isset($settings[$name]['currency_code']) ? $settings[$name]['currency_code'] : $default_currency->code,
        '#suffix' => '</div>',
      );
    }

    return $form;
  }

  public static function render($value) {
    return array(
      'content' => array(
        '#markup' => commerce_currency_format($value['amount'], $value['currency_code']),
      ),
    );
  }
}

/**
 * Implements hook_rules_action_info().
 */
function commerce_price_rules_action_info() {
  $actions = array();

  // Prepare an array of arithmetical actions for altering prices.
  $action_types = array(
    'commerce_price_add_price' => array(
      'label' => t('Add an amount to the price'),
      'amount description' => t('Specify a numeric amount to add to the price.'),
    ),
    'commerce_price_subtract_price' => array(
      'label' => t('Subtract an amount from the price'),
      'amount description' => t('Specify a numeric amount to subtract from the price.'),
    ),
    'commerce_price_multiply_price' => array(
      'label' => t('Multiply the price by some amount'),
      'amount description' => t('Specify the numeric amount by which to multiply the price.'),
    ),
    'commerce_price_divide_price' => array(
      'label' => t('Divide the price by some amount'),
      'amount description' => t('Specify a numeric amount by which to divide the price.'),
    ),
    'commerce_price_set_price' => array(
      'label' => t('Set the price to a specific amount'),
      'amount description' => t('Specify the numeric amount to set the price to.'),
    ),
  );

  // Define the action using a common set of parameters.
  foreach ($action_types as $key => $value) {
    $actions[$key] = array(
      'label' => $value['label'],
      'parameter' => array(
        'price' => array(
          'type' => 'commerce_price',
          'label' => t('Price'),
          'default value' => 'price',
        ),
        'amount' => array(
          'type' => 'decimal',
          'label' => t('Amount'),
          'description' => $value['amount description'],
        ),
      ),
      'group' => t('Commerce - price'),
    );
  }

  $actions['commerce_price_set_currency_code'] = array(
    'label' => t('Set the currency code'),
    'parameter' => array(
      'price' => array(
        'type' => 'commerce_price',
        'label' => t('Price'),
        'default value' => 'price',
      ),
      'currency_code' => array(
        'type' => 'text',
        'label' => t('Currency'),
      ),
    ),
    'group' => t('Commerce - price'),
  );

  return $actions;
}

/**
 * Rules action: add an amount to the price.
 */
function commerce_price_add_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Add an amount to the price'), $amount);
  }
}

/**
 * Rules action: subtract an amount from the price.
 */
function commerce_price_subtract_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Subtract an amount from the price'), -$amount);
  }
}

/**
 * Rules action: multiply the price by some amount.
 */
function commerce_price_multiply_price($price, $amount) {
  if (is_numeric($amount)) {
    $product = $price->getPrice() * $amount;

    $price->modifyPrice('rules', t('Multiply the price by some amount'), $product - $price->getPrice());
  }
}

/**
 * Rules action: divide the price by some amount.
 */
function commerce_price_divide_price($price, $amount) {
  if (is_numeric($amount)) {
    $quotient = $price->getPrice() / $amount;

    $price->modifyPrice('rules', t('Divide the price by some amount'), $quotient - $price->getPrice());
  }
}

/**
 * Rules action: set the price to a specific amount.
 */
function commerce_price_set_price($price, $amount) {
  if (is_numeric($amount)) {
    $price->modifyPrice('rules', t('Set the price to a specific amount'), $amount - $price->getPrice());
  }
}

/**
 * Rules action: set the currency code.
 */
function commerce_price_set_currency_code($price, $currency_code) {
  $price->modifyPrice('rules', t('Set the currency code'), 0, $currency_code);
}

/**
 * @}
 */
