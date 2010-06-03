<?php
// $Id$

/**
 * @file
 * Administrative callbacks for the Commerce UI module.
 */


/**
 * Builds the currency settings form.
 */
function commerce_currency_settings_form() {
  $form = array();

  // Build a currency options list from all defined currencies.
  $options = array();

  foreach (commerce_currencies() as $currency_code => $currency) {
    $options[$currency_code] = t('@code - !name', array('@code' => $currency->code, '@symbol' => $currency->symbol, '!name' => $currency->name));

    if (!empty($currency->symbol)) {
      $options[$currency_code] .= ' - ' . check_plain($currency->symbol);
    }
  }

  $form['commerce_default_currency'] = array(
    '#type' => 'select',
    '#title' => t('Default store currency'),
    '#description' => t('The default store currency will be used as the default for all price fields.'),
    '#options' => $options,
    '#default_value' => variable_get('commerce_default_currency', 'USD'),
  );

  return system_settings_form($form);
}
