<?php

/**
 * @file
 * Rules integration for the Price module.
 *
 * @addtogroup rules
 * @{
 */


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
    $settings += array($name => isset($info['default value']) ? $info['default value'] : array('amount' => NULL, 'currency_code' => NULL));
    $value = $settings[$name];

    // Legacy data stored price amount as a scalar value, so we convert it here
    // to the expected data structure.
    if (is_scalar($value)) {
      $value = array(
        'amount' => $value,
        'currency_code' => NULL,
      );
    }

    $currency_code = (empty($value['currency_code']) || $value['currency_code'] == 'default') ? commerce_default_currency() : $value['currency_code'];
    $currency = commerce_currency_load($currency_code);

    if (isset($value['amount']) && is_numeric($value['amount'])) {
      // Price amount should always be saved as integers (minor units), but in
      // case they're not we round them.
      $default_amount = strpos($value['amount'], '.') === FALSE ? $value['amount'] : commerce_round(COMMERCE_ROUND_HALF_UP, $value['amount']);
    }
    else {
      $default_amount = NULL;
    }

    $form[$name]['#element_validate'] = array('_commerce_price_rules_data_ui_element_validate');
    $form[$name]['amount'] = array(
      '#type' => 'textfield',
      '#default_value' => ($default_amount) ? commerce_currency_amount_to_decimal($default_amount, $currency_code) : NULL,
      '#size' => 10,
      '#required' => TRUE,
    );

    // Build a currency options list from all enabled currencies.
    $options = array();

    foreach (commerce_currencies(TRUE) as $currency_key => $currency_data) {
      $options[$currency_key] = check_plain($currency_data['code']);
    }

    // If the current currency value is not available, add it now with a message
    // in the help text explaining it.
    if (empty($options[$currency['code']])) {
      $options[$currency['code']] = check_plain($currency['code']);

      $description = t('The currency set for this action is not currently enabled. If you change it now, you will not be able to set it back.');
    }
    else {
      $description = '';
    }

    // If only one currency option is available, don't use a select list.
    if (count($options) == 1) {
      $form[$name]['amount']['#field_suffix'] = reset($options);

      $form[$name]['currency_code'] = array(
        '#type' => 'value',
        '#default_value' => key($options),
      );
    }
    else {
      $form[$name]['#attached']['css'][] = drupal_get_path('module', 'commerce_price') . '/theme/commerce_price.theme.css';
      $form[$name]['amount']['#prefix'] = '<div class="commerce-price-full">';

      $form[$name]['currency_code'] = array(
        '#type' => 'select',
        '#description' => $description,
        '#options' => $options,
        '#default_value' => $currency_code,
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
 * Implements hook_rules_condition_info().
 */
function commerce_price_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_price_compare_price'] = array(
    'label' => t('Price comparison'),
    'parameter' => array(
      'first_price' => array(
        'type' => 'commerce_price',
        'label' => t('First Price'),
        'description' => t('The price value that should be compared.'),
        'default mode' => 'selector',
      ),
      'operator' => array(
        'type' => 'text',
        'label' => t('Operator'),
        'description' => t('The comparison operator.'),
        'optional' => TRUE,
        'default value' => '=',
        'options list' => 'commerce_numeric_comparison_operator_options_list',
        'restriction' => 'input',
      ),
      'second_price' => array(
        'type' => 'commerce_price',
        'label' => t('Second Price'),
        'description' => t('The corresponding price value that should be compared against.'),
      ),
    ),
    'group' => t('Commerce Price'),
    'callbacks' => array(
      'execute' => 'commerce_price_rules_compare_price',
    ),
  );

  return $conditions;
}

/**
 * Condition callback: compares two price values.
 */
function commerce_price_rules_compare_price($first_price, $operator, $second_price) {
  $value1 = $first_price['amount'];
  $value2 = $second_price['amount'];

  // Convert amount of second price to match currency code of first price.
  if ($first_price['currency_code'] != $second_price['currency_code']) {
    $value2 = commerce_currency_convert($second_price['amount'], $second_price['currency_code'], $first_price['currency_code']);
  }

  // Make a quantity comparison based on the operator.
  switch ($operator) {
    case '<':
      return $value1 < $value2;
    case '<=':
      return $value1 <= $value2;
    case '=':
      return $value1 == $value2;
    case '>=':
      return $value1 >= $value2;
    case '>':
      return $value1 > $value2;
  }

  return FALSE;
}

/**
 * @}
 */
