<?php

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

  // Generate an option list from all user defined and module defined Views,
  // excluding the core Commerce cart block / form and order listing Views.
  $exclude = array('commerce_cart_block', 'commerce_cart_form', 'commerce_orders', 'commerce_user_orders');
  $default = variable_get('commerce_cart_contents_pane_view', 'commerce_cart_summary|default');

  foreach (views_get_all_views() as $view_id => $view_value) {
    // Only include line item Views, including a View that may be excluded but
    // has already been set to be the selected View some other way. The list of
    // excluded Views was added in as of Commerce 1.5, so we want to preserve
    // existing selections much like we do for Price fields with currency select
    // lists whose currency may have been disabled since the price was entered.
    if ($view_value->base_table == 'commerce_order') {
      foreach ($view_value->display as $display_id => $display_value) {
        $key = $view_id . '|' . $display_id;

        if (!in_array($view_id, $exclude) || $key == $default) {
          $options[$view_id][$view_id . '|' . $display_id] = $display_value->display_title;
        }
      }
    }
  }

  $form['commerce_cart_contents_pane_view'] = array(
    '#type' => 'select',
    '#title' => t('Cart contents View'),
    '#description' => t('Specify the line item listing View to use in the cart contents pane. It should not include line item summary links or any Views form elements (e.g. quantity textfiedls or delete buttons).') . '<br />' . t('You are not allowed to use any default Views defined by core Commerce modules except the cart summary View.'),
    '#options' => $options,
    '#default_value' => $default,
  );

  return $form;
}

/**
 * Checkout pane callback: returns the cart contents View for inclusion in the
 *   checkout form.
 */
function commerce_cart_contents_pane_checkout_form($form, &$form_state, $checkout_pane, $order) {
  $pane_form = array();

  drupal_add_css(drupal_get_path('module', 'commerce_cart') . '/theme/commerce_cart.theme.css');

  // Extract the View and display keys from the cart contents pane setting.
  list($view_id, $display_id) = explode('|', variable_get('commerce_cart_contents_pane_view', 'commerce_cart_summary|default'));

  $pane_form['cart_contents_view'] = array(
    '#markup' => commerce_embed_view($view_id, $display_id, array($order->order_id)),
  );

  return $pane_form;
}

/**
 * Checkout pane callback: returns the cart contents review data for the
 *   Review checkout pane.
 */
function commerce_cart_contents_pane_review($form, $form_state, $checkout_pane, $order) {
  drupal_add_css(drupal_get_path('module', 'commerce_cart') . '/theme/commerce_cart.theme.css');

  // Extract the View and display keys from the cart contents pane setting.
  list($view_id, $display_id) = explode('|', variable_get('commerce_cart_contents_pane_view', 'commerce_cart_summary|default'));

  return commerce_embed_view($view_id, $display_id, array($order->order_id));
}
