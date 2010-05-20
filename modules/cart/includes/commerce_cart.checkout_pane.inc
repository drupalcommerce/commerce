<?php
// $Id$

/**
 * @file
 * Checkout pane callback functions for the cart module.
 */


/**
 * Checkout pane callback: returns the cart contents pane's settings form.
 */
function commerce_cart_contents_pane_settings_form($checkout_pane) {
  $form = array();

  // Build an options array of Views available for the cart contents pane.
  // TODO: Update to list all line item View displays grouped by the View.
  $options = drupal_map_assoc(range(1, 5));

  $form['commerce_cart_contents_pane_view'] = array(
    '#type' => 'select',
    '#title' => t('Cart contents View'),
    '#description' => t('Specify the View to use in the cart contents pane.'),
    '#options' => $options,
    '#default_value' => variable_get('commerce_cart_contents_pane_view', ''),
  );

  return $form;
}

/**
 * Checkout pane callback: returns the cart contents View for inclusion in the
 *   checkout form.
 */
function commerce_cart_contents_pane_form($form_state, $checkout_pane, $order) {
  $form = array();

  $form['cart_contents_view'] = array(
    '#markup' => t('Temporary string, to be replaced by a line item View.'),
  );

  return $form;
}
