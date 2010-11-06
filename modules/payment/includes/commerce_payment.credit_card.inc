<?php
// $Id$

/**
 * @file
 * Credit-card helper functions for Drupal commerce.
 */

/**
 * Form callback: return a snippet credit card form.
 *
 * @param $default
 *   An array of default values for the available CC fields.
 * @param $fields
 *   An array specifying the CC fields that should be included on the form; the
 *     card number and expiration date fields are always present.
 */
function commerce_payment_credit_card_form($form, &$form_state, $default = array(), $fields = array()) {
  // Add a card type selector if specified.
  if (isset($fields['cc_type'])) {
    $form['cc_type'] = array(
      '#type' => 'select',
      '#title' => t('Card type'),
      '#options' => array_intersect_key(commerce_payment_credit_card_types(), drupal_map_assoc((array) $fields['cc_type'])),
      '#default_value' => $default['cc_type'],
    );
  }

  // Add a field for the credit card owner if specified.
  if (isset($fields['cc_owner'])) {
    $form['cc_owner'] = array(
      '#type' => 'textfield',
      '#title' => t('Card owner'),
      '#default_value' => $default['cc_owner'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
    );
  }

  // Always add a field for the credit card number.
  $form['cc_number'] = array(
    '#type' => 'textfield',
    '#title' => t('Card number'),
    '#default_value' => $default['cc_number'],
    '#attributes' => array('autocomplete' => 'off'),
    '#required' => TRUE,
    '#maxlength' => 19,
    '#size' => 20,
  );

  // Add fields for the credit card start date if specified.
  if (isset($fields['cc_start_date'])) {
    $form['cc_start_month'] = array(
      '#type' => 'select',
      '#title' => t('Start month'),
      '#options' => commerce_months(),
      '#default_value' => $default['cc_start_month'],
    );

    $form['cc_start_year'] = array(
      '#type' => 'select',
      '#title' => t('Start year'),
      '#options' => drupal_map_assoc(range(date('Y') - 10, date('Y'))),
      '#default_value' => $default['cc_start_year'],
    );
  }

  // Always add fields for the credit card expiration date.
  $form['cc_exp_month'] = array(
    '#type' => 'select',
    '#title' => t('Expiration month'),
    '#options' => commerce_months(),
    '#default_value' => $default['cc_exp_month'],
  );

  $form['cc_exp_year'] = array(
    '#type' => 'select',
    '#title' => t('Expiration Year'),
    '#options' => drupal_map_assoc(range(date('Y'), date('Y') + 20)),
    '#default_value' => $default['cc_exp_year'],
  );

  // Add a field for the card issue number if specified.
  if (isset($fields['cc_issue'])) {
    $form['cc_issue'] = array(
      '#type' => 'textfield',
      '#title' => t('Issue number'),
      '#default_value' => $default['cc_issue'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 2,
      '#size' => 2,
    );
  }

  // Add a field for the CVV if specified.
  if (isset($fields['cc_cvv'])) {
    $form['cc_cvv'] = array(
      '#type' => 'textfield',
      '#title' => !empty($fields['cc_cvv']) ? $fields['cc_cvv'] : t('CVV'),
      '#default_value' => $default['cc_cvv'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
    );
  }

  // Add a field for the issuing bank if specified.
  if (isset($fields['cc_bank'])) {
    $form['cc_bank'] = array(
      '#type' => 'textfield',
      '#title' => t('Issuing bank'),
      '#default_value' => $default['cc_bank'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
    );
  }

  return $form;
}

/**
 * Returns an associative array of credit card types.
 */
function commerce_payment_credit_card_types() {
  return array(
    'visa' => t('Visa'),
    'mastercard' => t('MasterCard'),
    'discover' => t('Discover'),
    'amex' => t('American Express'),
  );
}
