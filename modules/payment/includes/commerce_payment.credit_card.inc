<?php

/**
 * @file
 * Credit-card helper functions for Drupal commerce.
 */

/**
 * Returns a set of credit card form elements that payment method modules can
 *   incorporate into their submission form callbacks.
 *
 * @param $default
 *   An array of default values for the available CC fields.
 * @param $fields
 *   An array specifying the CC fields that should be included on the form; the
 *     card number and expiration date fields are always present.
 */
function commerce_payment_credit_card_form($fields = array(), $default = array()) {
  // Merge default values into the default array.
  $default += array(
    'type' => '',
    'owner' => '',
    'number' => '',
    'start_month' => '',
    'start_year' => '',
    'exp_month' => '',
    'exp_year' => '',
    'issue' => '',
    'code' => '',
    'bank' => '',
  );

  $form['credit_card'] = array(
    '#tree' => TRUE,
  );

  // Add a card type selector if specified.
  if (isset($fields['type'])) {
    $form['credit_card']['type'] = array(
      '#type' => 'select',
      '#title' => t('Card type'),
      '#options' => array_intersect_key(commerce_payment_credit_card_types(), drupal_map_assoc((array) $fields['type'])),
      '#default_value' => $default['type'],
    );
  }

  // Add a field for the credit card owner if specified.
  if (isset($fields['owner'])) {
    $form['credit_card']['owner'] = array(
      '#type' => 'textfield',
      '#title' => t('Card owner'),
      '#default_value' => $default['owner'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
    );
  }

  // Always add a field for the credit card number.
  $form['credit_card']['number'] = array(
    '#type' => 'textfield',
    '#title' => t('Card number'),
    '#default_value' => $default['number'],
    '#attributes' => array('autocomplete' => 'off'),
    '#required' => TRUE,
    '#maxlength' => 19,
    '#size' => 20,
  );

  // Add fields for the credit card start date if specified.
  if (isset($fields['start_date'])) {
    $form['credit_card']['start_month'] = array(
      '#type' => 'select',
      '#title' => t('Start month'),
      '#options' => commerce_months(),
      '#default_value' => $default['start_month'],
    );

    $form['credit_card']['start_year'] = array(
      '#type' => 'select',
      '#title' => t('Start year'),
      '#options' => drupal_map_assoc(range(date('Y') - 10, date('Y'))),
      '#default_value' => $default['start_year'],
    );
  }

  // Always add fields for the credit card expiration date.
  $form['credit_card']['exp_month'] = array(
    '#type' => 'select',
    '#title' => t('Expiration month'),
    '#options' => commerce_months(),
    '#default_value' => $default['exp_month'],
  );

  $form['credit_card']['exp_year'] = array(
    '#type' => 'select',
    '#title' => t('Expiration Year'),
    '#options' => drupal_map_assoc(range(date('Y'), date('Y') + 20)),
    '#default_value' => $default['exp_year'],
  );

  // Add a field for the card issue number if specified.
  if (isset($fields['issue'])) {
    $form['credit_card']['issue'] = array(
      '#type' => 'textfield',
      '#title' => t('Issue number'),
      '#default_value' => $default['issue'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => empty($fields['issue']) ? FALSE : TRUE,
      '#maxlength' => 2,
      '#size' => 2,
    );
  }

  // Add a field for the security code if specified.
  if (isset($fields['code'])) {
    $form['credit_card']['code'] = array(
      '#type' => 'textfield',
      '#title' => !empty($fields['code']) ? $fields['code'] : t('Security code'),
      '#default_value' => $default['code'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
    );
  }

  // Add a field for the issuing bank if specified.
  if (isset($fields['bank'])) {
    $form['credit_card']['bank'] = array(
      '#type' => 'textfield',
      '#title' => t('Issuing bank'),
      '#default_value' => $default['bank'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
    );
  }

  return $form;
}

/**
 * Validates a set of credit card details entered via the credit card form.
 *
 * @param $details
 *   An array of credit card details as retrieved from the credit card array in
 *     the form values of a form containing the credit card form.
 *
 * @return
 *   TRUE or FALSE indicating the validity of all the data.
 *
 * @see commerce_payment_credit_card_form()
 */
function commerce_payment_credit_card_validate($details, $settings) {
  $prefix = implode('][', $settings['form_parents']) . '][';
  $valid = TRUE;

  // Validate the credit card number.
  if (!commerce_payment_validate_credit_card_number($details['number'])) {
    form_set_error($prefix . 'number', t('You have entered an invalid credit card number.'));
    $valid = FALSE;
  }

  // Validate the expiration date.
  if (($invalid = commerce_payment_validate_credit_card_exp_date($details['exp_month'], $details['exp_year'])) !== TRUE) {
    form_set_error($prefix . 'exp_' . $invalid, t('You have entered an expired credit card.'));
    $valid = FALSE;
  }

  // Validate the security code if present.
  if (isset($details['code']) && !commerce_payment_validate_credit_card_security_code($details['number'], $details['code'])) {
    form_set_error($prefix . 'code', t('You have entered an invalid card security code.'));
    $valid = FALSE;
  }

  // Validate the start date if present.
  if (isset($details['start_date']) && ($invalid = commerce_payment_validate_credit_card_start_date($details['exp_month'], $details['exp_year'])) !== TRUE) {
    form_set_error($prefix . 'start_' . $invalid, t('Your have entered an invalid start date.'));
    $valid = FALSE;
  }

  // Validate the issue number if present.
  if (isset($details['issue']) && !commerce_payment_validate_credit_card_issue($details['issue'])) {
    form_set_error($prefix . 'issue', t('You have entered an invalid issue number.'));
    $valid = FALSE;
  }

  return $valid;
}

/**
 * Validates a credit card number using card types and the Luhn algorithm.
 *
 * @param $number
 *   The credit card number to validate.
 * @param $card_types
 *   An array of credit card types containing any of the keys from the array
 *     returned by commerce_payment_credit_card_types(). If any are specified,
 *     only those card types will pass validation.
 *
 * @return
 *   TRUE or FALSE indicating the number's validity.
 *
 * @see http://www.merriampark.com/anatomycc.htm
 * @see commerce_payment_credit_card_types()
 */
function commerce_payment_validate_credit_card_number($number, $card_types = array()) {
  // Ensure every character in the number is numeric.
  if (!ctype_digit($number)) {
    return FALSE;
  }

  // Check for valid credit card types if specified.
  if (!empty($card_types)) {
    switch (substr($number, 0, 1)) {
      case '3':
        $card_type = 'amex';
        break;
      case '4':
        $card_type = 'visa';
        break;
      case '5':
        $card_type = 'mastercard';
        break;
      case '6':
        $card_type = 'discover';
        break;
    }

    if (!in_array($card_type, $card_types)) {
      return FALSE;
    }
  }

  // Validate the number using the Luhn algorithm.
  $total = 0;

  for ($i = 0; $i < strlen($number); $i++) {
    $digit = substr($number, $i, 1);
    if ((strlen($number) - $i - 1) % 2) {
      $digit *= 2;
      if ($digit > 9) {
        $digit -= 9;
      }
    }
    $total += $digit;
  }

  if ($total % 10 != 0) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Validates a credit card start date.
 *
 * @param $month
 *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
 * @param $year
 *   The 4-digit numeric representation of the year, i.e. 2010.
 *
 * @return
 *   TRUE for cards whose start date is blank (both month and year) or in the
 *     past, 'year' or 'month' for expired cards indicating which value should
 *     receive the error.
 */
function commerce_payment_validate_credit_card_start_date($month, $year) {
  if (empty($month) && empty($year)) {
    return TRUE;
  }

  if (empty($month) || empty($year)) {
    return empty($month) ? 'month' : 'year';
  }

  if ($year > date('Y')) {
    return 'year';
  }
  elseif ($year == date('Y')) {
    if ($month > date('n')) {
      return 'month';
    }
  }

  return TRUE;
}

/**
 * Validates a credit card expiration date.
 *
 * @param $month
 *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
 * @param $year
 *   The 4-digit numeric representation of the year, i.e. 2010.
 *
 * @return
 *   TRUE for non-expired cards, 'year' or 'month' for expired cards indicating
 *     which value should receive the error.
 */
function commerce_payment_validate_credit_card_exp_date($month, $year) {
  if ($year < date('Y')) {
    return 'year';
  }
  elseif ($year == date('Y')) {
    if ($month < date('n')) {
      return 'month';
    }
  }

  return TRUE;
}

/**
 * Validates that an issue number is numeric if present.
 */
function commerce_payment_validate_credit_card_issue($issue) {
  if (empty($issue) || (is_numeric($issue) && $issue > 0)) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Validates a card security code based on the type of the credit card.
 *
 * @param $number
 *   The number of the credit card to validate the security code against.
 * @param $code
 *   The card security code to validate with the given number.
 *
 * @return
 *   TRUE or FALSE indicating the security code's validity.
 */
function commerce_payment_validate_credit_card_security_code($number, $code) {
  // Ensure the code is numeric.
  if (!ctype_digit($code)) {
    return FALSE;
  }

  // Check the length based on the type of the credit card.
  switch (substr($number, 0, 1)) {
    case '3':
      return strlen($code) == 4;

    case '4':
    case '5':
    case '6':
      return strlen($code) == 3;
  }
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
    'maestro' => t('Maestro'),
    'delta' => t('Visa Debit / Delta'),
    'dc' => t("Diner's Club"),
    'jcb' => t('JCB'),
    'laser' => t('Laser')
  );
}
