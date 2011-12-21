<?php

/**
 * @file
 * Administrative page callbacks for the Product Pricing UI module.
 */


/**
 * Builds the product sell price calculation Rules Overview page.
 */
function commerce_product_pricing_ui_sell_price_rules() {
  RulesPluginUI::$basePath = 'admin/commerce/config/product-pricing/rules';
  $options = array('show plugin' => FALSE);

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled sell price calculation rules') . '</h3>';

  $conditions = array('event' => 'commerce_product_calculate_sell_price', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['enabled']['rules']['#empty'] = t('There are no active sell price calculation rules.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled sell price calculation rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions, $options);
  $content['disabled']['rules']['#empty'] = t('There are no disabled sell price calculation rules.');

  return $content;
}

/**
 * Displays the settings form for managing product sell price pre-calculation.
 */
function commerce_product_pre_calculation_settings_form($form, &$form_state) {
  // Count the number of rows in the price pre-calculation table.
  $query = db_select('commerce_calculated_price')
    ->fields('commerce_calculated_price', array('created'))
    ->condition('module', 'commerce_product_pricing')
    ->condition('entity_type', 'commerce_product')
    ->condition('field_name', 'commerce_price');

  $count_query = clone($query);
  $count = $count_query->countQuery()->execute()->fetchField();

  // If there are rows in the table or price pre-calculation is enabled, show
  // the management fieldset with its action buttons.
  if ($count > 0 || variable_get('commerce_product_sell_price_pre_calculation', 'disabled') != 'disabled') {
    // Build a description for the fieldset indicating how many rows are in the
    // table and when they were last processed.
    $description = format_plural($count,
      t('There is 1 product sell price in the calculated price table.'),
      t('There are @count product sell prices in the calculated price table.'));

    // If there are prices, add the timestamp for the last calculation.
    if ($count > 0) {
      $description .= ' ' . t('The last calculation occured on @date.', array('@date' => format_date($query->execute()->fetchField(), 'short')));
    }

    $form['database'] = array(
      '#type' => 'fieldset',
      '#title' => t('Manage calculated prices'),
      '#description' => '<p>' . $description . '</p>',
    );

    if ($count > 0) {
      $form['database']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete product sell prices'),
      );
    }
    else {
      $form['database']['batch_calculate'] = array(
        '#type' => 'submit',
        '#value' => t('Batch calculate prices now'),
      );
    }
  }

  $form['commerce_product_sell_price_pre_calculation'] = array(
    '#type' => 'radios',
    '#title' => t('Sell price pre-calculation method'),
    '#description' => t('If pre-calculation is disabled, code that integrates calculated prices into queries and price displays will ignore any existing calculated prices.'),
    '#options' => array(
      'disabled' => t('Disabled'),
      'manual_batch' => t('Manual batch calculation'),

      // TODO: Support automated re-calculation when Rules or Products that have
      // pre-calculated prices are updated.
      // 'automated_batch' => t('Manual batch pre-calculation with automated updates'),
    ),
    '#default_value' => variable_get('commerce_product_sell_price_pre_calculation', 'disabled'),
  );

  // If price pre-calculation is enabled, prevent options to bypass rules.
  if (variable_get('commerce_product_sell_price_pre_calculation', 'disabled') != 'disabled') {
    // Build an options list of all valid pricing rules and keep track of those
    // that are invalid.
    $options = array();
    $invalid = array();

    $event = rules_get_cache('event_commerce_product_calculate_sell_price');

    foreach ($event as $rule) {
      if (commerce_product_valid_pre_calculation_rule($rule, TRUE)) {
        $options[$rule->name] = t('@label [@name]', array('@label' => $rule->label(), '@name' => $rule->name));
      }
      else {
        $invalid[$rule->name] = t('@label [@name]', array('@label' => $rule->label(), '@name' => $rule->name));
      }
    }

    if (!empty($options)) {
      $form['commerce_product_sell_price_pre_calculation_rules_bypass'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Bypass these rules when pre-calculating sell prices'),
        '#description' => t('When pre-calculating sell prices, all valid rules will be included in calculating the variations of every price unless specified here to be bypassed.'),
        '#options' => $options,
        '#default_value' => variable_get('commerce_product_sell_price_pre_calculation_rules_bypass', array()),
      );
    }

    if (!empty($invalid)) {
      $form['invalid_rules'] = array(
        '#type' => 'fieldset',
        '#title' => t('Invalid rules for pre-calculation'),
        '#description' => t('The rules listed below are invalid for sell price pre-calculation either because they do not have any conditions (and therefore do not require pre-calculation), they reference product data in a condition data selector, or they reference non-product data in an action data selector.'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['invalid_rules']['list'] = array(
        '#markup' => theme('item_list', array('items' => $invalid)),
      );
    }
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Submit callback: process the product sell price pre-calculation form.
 */
function commerce_product_pre_calculation_settings_form_submit($form, &$form_state) {
  $values = $form_state['values'];

  // Save variables on the form regardless of the button pressed.
  variable_set('commerce_product_sell_price_pre_calculation', $values['commerce_product_sell_price_pre_calculation']);

  if (!empty($form_state['values']['commerce_product_sell_price_pre_calculation_rules_bypass'])) {
    variable_set('commerce_product_sell_price_pre_calculation_rules_bypass', $form_state['values']['commerce_product_sell_price_pre_calculation_rules_bypass']);
  }

  // React to the management buttons if they were used to submit the form.

  // If the batch calculation button was pressed, setup the batch now.
  if (!empty($values['batch_calculate']) && $values['op'] == $values['batch_calculate']) {
    commerce_product_batch_pre_calculate_sell_prices();
  }

  // TODO: Expand the API to allow for deletion of pre-calculated prices and
  // get this query the heck out of here.
  if (!empty($values['delete']) && $values['op'] == $values['delete']) {
    db_delete('commerce_calculated_price')
      ->condition('module', 'commerce_product_pricing')
      ->condition('entity_type', 'commerce_product')
      ->condition('field_name', 'commerce_price')
      ->execute();
  }
}
