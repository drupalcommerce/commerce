<?php

/**
 * @file
 * Administrative callbacks for the Commerce UI module.
 */


/**
 * Builds the currency settings form.
 */
function commerce_currency_settings_form($form, &$form_state) {
  // Build a currency options list from all defined currencies.
  $options = array();

  foreach (commerce_currencies(FALSE, TRUE) as $currency_code => $currency) {
    $options[$currency_code] = t('@code - !name', array('@code' => $currency['code'], '@symbol' => $currency['symbol'], '!name' => $currency['name']));

    if (!empty($currency['symbol'])) {
      $options[$currency_code] .= ' - ' . check_plain($currency['symbol']);
    }
  }

  $form['commerce_default_currency'] = array(
    '#type' => 'select',
    '#title' => t('Default store currency'),
    '#description' => t('The default store currency will be used as the default for all price fields.'),
    '#options' => $options,
    '#default_value' => commerce_default_currency(),
  );

  // Place the enabled currencies checkboxes in a fieldset so the full list
  // doesn't spam the administrator when viewing the page.
  $form['enabled_currencies'] = array(
    '#type' => 'fieldset',
    '#title' => t('Enabled currencies'),
    '#description' => t('Only enabled currencies will be visible to users when entering prices. The default currency will always be enabled.'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['enabled_currencies']['commerce_enabled_currencies'] = array(
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => variable_get('commerce_enabled_currencies', array('USD' => 'USD')),
  );

  $form['#validate'][] = 'commerce_currency_settings_form_validate';

  return system_settings_form($form);
}

/**
 * Form validate handler for the currency settings form.
 */
function commerce_currency_settings_form_validate($form, &$form_state) {
  // Ensure the default currency is always enabled.
  $default = $form_state['values']['commerce_default_currency'];
  $form_state['values']['commerce_enabled_currencies'][$default] = $default;
}
