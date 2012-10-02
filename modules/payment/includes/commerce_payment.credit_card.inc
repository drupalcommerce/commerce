<?php

/**
 * @file
 * Credit-card helper functions for Drupal commerce.
 */

/**
 * Returns a set of credit card form elements that payment method modules can
 *   incorporate into their submission form callbacks.
 *
 * @param $fields
 *   An array specifying the CC fields that should be included on the form; the
 *     card number and expiration date fields are always present.
 * @param $default
 *   An array of default values for the available CC fields.
 */
function commerce_payment_credit_card_form($fields = array(), $default = array()) {
  // Merge default values into the default array.
  $default += array(
    'type' => '',
    'owner' => '',
    'number' => '',
    'start_month' => '',
    'start_year' => date('Y') - 5,
    'exp_month' => date('m'),
    'exp_year' => date('Y'),
    'issue' => '',
    'code' => '',
    'bank' => '',
  );

  $current_year_2 = date('y');
  $current_year_4 = date('Y');

  $form['credit_card'] = array(
    '#tree' => TRUE,
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'commerce_payment') . '/theme/commerce_payment.theme.css'),
    ),
  );

  // Add a card type selector if specified.
  if (isset($fields['type'])) {
    $form['credit_card']['type'] = array(
      '#type' => 'select',
      '#title' => t('Card type'),
      '#options' => array_intersect_key(commerce_payment_credit_card_types(), drupal_map_assoc((array) $fields['type'])),
      '#default_value' => $default['type'],
    );
    $form['credit_card']['valid_types'] = array(
      '#type' => 'value',
      '#value' => $fields['type'],
    );
  }
  else {
    $form['credit_card']['valid_types'] = array(
      '#type' => 'value',
      '#value' => array(),
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
      '#title' => t('Start date'),
      '#options' => drupal_map_assoc(array_keys(commerce_months())),
      '#default_value' => strlen($default['start_month']) == 1 ? '0' . $default['start_month'] : $default['start_month'],
      '#required' => TRUE,
      '#prefix' => '<div class="commerce-credit-card-start">',
      '#suffix' => '<span class="commerce-month-year-divider">/</span>',
    );

    // Build a year select list that uses a 4 digit key with a 2 digit value.
    $options = array();

    for ($i = -10; $i < 1; $i++) {
      $options[$current_year_4 + $i] = str_pad($current_year_2 + $i, 2, '0', STR_PAD_LEFT);
    }

    $form['credit_card']['start_year'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default['start_year'],
      '#suffix' => '</div>',
    );
  }

  // Always add fields for the credit card expiration date.
  $form['credit_card']['exp_month'] = array(
    '#type' => 'select',
    '#title' => t('Expiration'),
    '#options' => drupal_map_assoc(array_keys(commerce_months())),
    '#default_value' => strlen($default['exp_month']) == 1 ? '0' . $default['exp_month'] : $default['exp_month'],
    '#required' => TRUE,
    '#prefix' => '<div class="commerce-credit-card-expiration">',
    '#suffix' => '<span class="commerce-month-year-divider">/</span>',
  );

  // Build a year select list that uses a 4 digit key with a 2 digit value.
  $options = array();

  for ($i = 0; $i < 20; $i++) {
    $options[$current_year_4 + $i] = str_pad($current_year_2 + $i, 2, '0', STR_PAD_LEFT);
  }

  $form['credit_card']['exp_year'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $default['exp_year'],
    '#suffix' => '</div>',
  );

  // Add a field for the card issue number if specified.
  if (isset($fields['issue'])) {
    $form['credit_card']['issue'] = array(
      '#type' => 'textfield',
      '#title' => t('Issue number', array(), array('context' => 'credit card issue number for card types that require it')),
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
 *   the form values of a form containing the credit card form.
 * @param $settings
 *   Settings used for calling validation functions and setting form errors:
 *   - form_parents: an array of parent elements identifying where the credit
 *     card form was situated in the form array
 *
 * @return
 *   TRUE or FALSE indicating the validity of all the data.
 *
 * @see commerce_payment_credit_card_form()
 */
function commerce_payment_credit_card_validate($details, $settings) {
  $prefix = implode('][', $settings['form_parents']) . '][';
  $valid = TRUE;

  // Validate the credit card type.
  if (!empty($details['valid_types'])) {
    $type = commerce_payment_validate_credit_card_type($details['number'], $details['valid_types']);

    if ($type === FALSE) {
      form_set_error($prefix . 'type', t('You have entered a credit card number of an unsupported card type.'));
      $valid = FALSE;
    }
    elseif ($type != $details['type']) {
      form_set_error($prefix . 'number', t('You have entered a credit card number that does not match the type selected.'));
      $valid = FALSE;
    }
  }

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
 * Validates a credit card number using an array of approved card types.
 *
 * @param $number
 *   The credit card number to validate.
 * @param $card_types
 *   An array of credit card types containing any of the keys from the array
 *   returned by commerce_payment_credit_card_types(). Only numbers determined
 *   to be of the types specified will pass validation. This determination is
 *   based on the length of the number and the valid number ranges for the
 *   various types of known credit card types.
 *
 * @return
 *   FALSE if a number is not valid based on approved credit card types or the
 *   credit card type if it is valid and coud be determined.
 *
 * @see http://en.wikipedia.org/wiki/Bank_card_number#Issuer_Identification_Number_.28IIN.29
 * @see commerce_payment_credit_card_types()
 */
function commerce_payment_validate_credit_card_type($number, $card_types) {
  $strlen = strlen($number);

  // Provide a check on the first digit (and card length if applicable).
  switch (substr($number, 0, 1)) {
    case '3':
      // American Express begins with 3 and is 15 numbers.
      if ($strlen == 15 && in_array('amex', $card_types)) {
        return 'amex';
      }

      // JCB begins with 3528-3589 and is 16 numbers.
      if ($strlen == 16 && in_array('jcb', $card_types)) {
        return 'jcb';
      }

      // Carte Blanche begins with 300-305 and is 14 numbers.
      // Diners Club International begins 36 and is 14 numbers.
      if ($strlen == 14) {
        $initial = (int) substr($number, 0, 3);

        if ($initial >= 300 && $initial <= 305 && in_array('cb', $card_types)) {
          return 'cb';
        }

        if (substr($number, 0, 2) == '36' && in_array('dci', $card_types)) {
          return 'dci';
        }
      }

      return FALSE;

    case '4':
      $initial = (int) substr($number, 0, 4);
      $return = FALSE;

      if ($strlen == 16) {
        // Visa begins with 4 and is 16 numbers.
        if (in_array('visa', $card_types)) {
          $return = 'visa';
        }

        // Visa Electron begins with 4026, 417500, 4256, 4508, 4844, 4913, or
        // 4917 and is 16 numbers.
        if (in_array($initial, array(4026, 4256, 4508, 4844, 4913, 4917)) || substr($number, 0, 6) == '417500') {
          $return = in_array('visaelectron', $card_types) ? 'visaelectron' : FALSE;
        }
      }

      // Switch begins with 4903, 4905, 4911, or 4936 and is 16, 18, or 19
      // numbers.
      if (in_array($strlen, array(16, 18, 19)) &&
        in_array($initial, array(4903, 4905, 4911, 4936))) {
        $return = in_array('switch', $card_types) ? 'switch' : FALSE;
      }

      return $return;

    case '5':
      // MasterCard begins with 51-55 and is 16 numbers.
      // Diners Club begins with 54 or 55 and is 16 numbers.
      if ($strlen == 16) {
        $initial = (int) substr($number, 0, 2);

        if ($initial >= 51 && $initial <= 55 && in_array('mastercard', $card_types)) {
          return 'mastercard';
        }

        if ($initial >= 54 && $initial <= 55 && in_array('dc', $card_types)) {
          return 'dc';
        }
      }

      // Switch begins with 564182 and is 16, 18, or 19 numbers.
      if (in_array('switch', $card_types) && substr($number, 0, 6) == '564182' &&
        in_array($strlen, array(16, 18, 19))) {
        return 'switch';
      }

      // Maestro begins with 5018, 5020, or 5038 and is 12-19 numbers.
      if (in_array('maestro', $card_types) && $strlen >= 12 && $strlen <= 19 &&
        in_array(substr($number, 0, 4), array(5018, 5020, 5038))) {
        return 'maestro';
      }

      return FALSE;

    case '6':
      // Discover begins with 6011, 622126-622925, 644-649, or 65 and is 16
      // numbers.
      if ($strlen == 16 && in_array('discover', $card_types)) {
        if (substr($number, 0, 4) == '6011' || substr($number, 0, 2) == '65') {
          return 'discover';
        }

        $initial = (int) substr($number, 0, 6);

        if ($initial >= 622126 && $initial <= 622925) {
          return 'discover';
        }

        $initial = (int) substr($number, 0, 3);

        if ($initial >= 644 && $initial <= 649) {
          return 'discover';
        }
      }

      // Laser begins with 6304, 6706, 6771, or 6709 and is 16-19 numbers.
      $initial = (int) substr($number, 0, 4);

      if (in_array('laser', $card_types) && $strlen >= 16 && $strlen <= 19 &&
        in_array($initial, array(6304, 6706, 6771, 6709))) {
        return 'laser';
      }

      // Maestro begins with 6304, 6759, 6761, or 6763 and is 12-19 numbers.
      if (in_array('maestro', $card_types) && $strlen >= 12 && $strlen <= 19 &&
        in_array($initial, array(6304, 6759, 6761, 6763))) {
        return 'maestro';
      }

      // Solo begins with 6334 or 6767 and is 16, 18, or 19 numbers.
      if (in_array('solo', $card_types) && in_array($strlen, array(16, 18, 19)) &&
        in_array($initial, array(6334, 6767))) {
        return 'solo';
      }

      // Switch begins with 633110, 6333, or 6759 and is 16, 18, or 19 numbers.
      if (in_array('switch', $card_types) && in_array($strlen, array(16, 18, 19)) &&
        (in_array($initial, array(6333, 6759)) || substr($number, 0, 6) == 633110)) {
        return 'switch';
      }

      return FALSE;
  }

  return FALSE;
}

/**
 * Validates a credit card number using the Luhn algorithm.
 *
 * @param $number
 *   The credit card number to validate.
 *
 * @return
 *   TRUE or FALSE indicating the number's validity.
 *
 * @see http://www.merriampark.com/anatomycc.htm
 */
function commerce_payment_validate_credit_card_number($number) {
  // Ensure every character in the number is numeric.
  if (!ctype_digit($number)) {
    return FALSE;
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
 *   past, 'year' or 'month' for expired cards indicating which value should
 *   receive the error.
 */
function commerce_payment_validate_credit_card_start_date($month, $year) {
  if (empty($month) && empty($year)) {
    return TRUE;
  }

  if (empty($month) || empty($year)) {
    return empty($month) ? 'month' : 'year';
  }

  if ($month < 1 || $month > 12) {
    return 'month';
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
 *   which value should receive the error.
 */
function commerce_payment_validate_credit_card_exp_date($month, $year) {
  if ($month < 1 || $month > 12) {
    return 'month';
  }

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
      if (strlen($number) == 15) {
        return strlen($code) == 4;
      }
      else {
        return strlen($code) == 3;
      }

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
    'amex' => t('American Express'),
    'discover' => t('Discover Card'),
    'dc' => t("Diners Club"),
    'dci' => t("Diners Club International"),
    'cb' => t("Carte Blanche"),
    'jcb' => t('JCB'),
    'maestro' => t('Maestro'),
    'visaelectron' => t('Visa Electron'),
    'laser' => t('Laser'),
    'solo' => t('Solo'),
    'switch' => t('Switch'),
  );
}
