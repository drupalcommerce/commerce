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
  $options = array();

 	// Generate an option list from all user defined and module defined views.
 	foreach (views_get_all_views() as $view_id => $view_value) {
    // Only include line item Views.
    if ($view_value->base_table == 'commerce_order') {
     	foreach ($view_value->display as $display_id => $display_value) {
        $options[check_plain($view_id)][$view_id .'|'. $display_id] = check_plain($display_value->display_title);
      }
   	}
 	}

  $form['commerce_cart_contents_pane_view'] = array(
    '#type' => 'select',
    '#title' => t('Cart contents View'),
    '#description' => t('Specify the View to use in the cart contents pane.'),
    '#options' => $options,
    '#default_value' => variable_get('commerce_cart_contents_pane_view', 'commerce_cart|default'),
  );

  return $form;
}

/**
 * Checkout pane callback: returns the cart contents View for inclusion in the
 *   checkout form.
 */
function commerce_cart_contents_pane_checkout_form($pane_values, $checkout_pane, $order) {
  $form = array();

  // Extract the View and display keys from the cart contents pane setting.
  list($view_id, $display_id) = explode('|', variable_get('commerce_cart_contents_pane_view', 'commerce_cart|default'));

  $form['cart_contents_view'] = array(
    '#markup' => commerce_embed_view($view_id, $display_id, $order->order_id),
  );

  return $form;
}

/**
 * Checkout pane callback: returns the cart contents review data for the
 *   optional checkout review page.
 */
function commerce_cart_contents_pane_review($form_state, $order) {
  // Extract the View and display keys from the cart contents pane setting.
  list($view_id, $display_id) = explode('|', variable_get('commerce_cart_contents_pane_view', 'commerce_cart|default'));

  return commerce_embed_view($view_id, $display_id, $order->order_id);
}
