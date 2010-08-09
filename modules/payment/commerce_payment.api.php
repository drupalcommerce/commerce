<?php
// $Id$

/**
 * @file
 * Documentation for Drupal Commerce Payment API.
 */

/**
 * Define payment methods.
 *
 * Available keys:
 *  - (mandatory) title: the title of the payment method, as displayed in
 *    administrative screens.
 *  - (mandatory) description: the description of the payment method.
 *  - (optional) default settings: an associative array of default settings
 *    for this payment method.
 *  - (optional) base: the base callback for this payment method. The default
 *    is the key of the payment method.
 *  - (optional) callbacks: an associative array of callback functions.
 *    The default for each key is $base . '_' . $callback. Available keys:
 *      - settings: a callback for the settings form
 *      - submit_form: the form displayed during the checkout
 *      - submit_form_validate: the validation callback for the pane form
 *      - submit_form_submit: the submit callback for the pane form
 *      - redirect_form: the form displayed during (optional) redirection
 *      - redirect_form_validate: the validation callback for the redirect form
 *      - redirect_form_submit: the validation callback for the redirect form
 */
function hook_commerce_payment_method_info() {
  $method['shiny_gateway'] = array(
    'title' => t('Shiny gateway'),
    'description' => t('Secure alliance payment via the Shiny gateway.'),
    'default settings' => array(
      'owner name' => 'Malcolm Reynolds',
      'class code' => '03-K64',
    ),
  );
  return $method;
}
