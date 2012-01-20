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
      if (strpos($default_amount, '.') === FALSE || strpos($default_amount, '.') > strlen($default_amount) - $currency['decimals']) {
        $default_amount = number_format($default_amount, $currency['decimals'], '.', '');
      }

      $default_currency_code = $settings[$name]['currency_code'];
    }
    else {
      $default_amount = NULL;
    }

    // Load the default currency for this instance.
    $default_currency = commerce_currency_load($default_currency_code);

    $form[$name]['#attached']['css'][] = drupal_get_path('module', 'commerce_price') . '/theme/commerce_price.theme.css';

    $form[$name]['amount'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_amount,
      '#size' => 10,
    );

    // Build a currency options list from all enabled currencies.
    $options = array();

    foreach (commerce_currencies(TRUE) as $currency_code => $currency) {
      $options[$currency_code] = check_plain($currency['code']);
    }

    // If the current currency value is not available, add it now with a
    // message in the help text explaining it.
    if (empty($options[$default_currency['code']])) {
      $options[$default_currency['code']] = check_plain($default_currency['code']);

      $description = t('The currency set for this action is not currently enabled. If you change it now, you will not be able to set it back.');
    }
    else {
      $description = '';
    }

    // If only one currency option is available, don't use a select list.
    if (count($options) == 1) {
      $currency_code = key($options);

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
        '#default_value' => isset($settings[$name]['currency_code']) ? $settings[$name]['currency_code'] : $default_currency['code'],
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
 * @}
 */
