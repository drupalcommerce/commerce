<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the Product Reference UI module.
 */


/**
 * Builds the product sell price calculation Rules Overview page.
 */
function commerce_product_reference_ui_sell_price_rules() {
  RulesPluginUI::$basePath = 'admin/commerce/products/pricing-rules';

  $content['enabled']['title']['#markup'] = '<h3>' . t('Enabled sell price calculation rules') . '</h3>';

  $conditions = array('event' => 'commerce_product_calculate_sell_price', 'plugin' => 'reaction rule', 'active' => TRUE);
  $content['enabled']['rules'] = RulesPluginUI::overviewTable($conditions);
  $content['enabled']['rules']['#empty'] = t('There are no active sell price calculation rules.');

  $content['disabled']['title']['#markup'] = '<h3>' . t('Disabled sell price calculation rules') . '</h3>';

  $conditions['active'] = FALSE;
  $content['disabled']['rules'] = RulesPluginUI::overviewTable($conditions);
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
    ->condition('module', 'commerce_product_reference')
    ->condition('entity_type', 'commerce_product')
    ->condition('field_name', 'purchase_price')
    ->execute();

  $count = $query->rowCount();

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
      $description .= ' ' . t('The last calculation occured on @date.', array('@date' => format_date($query->fetchField(), 'short')));
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

  // React to the management buttons if they were used to submit the form.

  // TODO: Update the API to actually support a batch pre-calculation instead
  // of doing them all at once now.
  if (!empty($values['batch_calculate']) && $values['op'] == $values['batch_calculate']) {
    commerce_product_pre_calculate_sell_prices();
  }

  // TODO: Expand the API to allow for deletion of pre-calculated prices and
  // get this query the heck out of here.
  if (!empty($values['delete']) && $values['op'] == $values['delete']) {
    db_delete('commerce_calculated_price')
      ->condition('module', 'commerce_product_reference')
      ->condition('entity_type', 'commerce_product')
      ->condition('field_name', 'purchase_price')
      ->execute();
  }
}
