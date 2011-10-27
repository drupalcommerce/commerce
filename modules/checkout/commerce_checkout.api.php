<?php

/**
 * @file
 * Hooks provided by the Checkout module.
 */


/**
 * Routes checkout/%commerce_order* to an alternate URL if necessary.
 *
 * The checkout module uses two URLs for the checkout form, displaying a form
 * specific to the checkout page indicated by the URL.
 *
 * - checkout/%commerce_order: used for the first checkout page
 * - checkout/%commerce_order/%commerce_checkout_page: used for all subsequent
 *   checkout pages
 *
 * The page callback for these URLs checks the user's access to the requested
 * checkout page for the given order and to make sure the order has line items.
 * After these two checks, it gives other modules an opportunity to evaluate the
 * order and checkout page to see if any other redirection is necessary. This
 * hook should not be used to alter the output at the actual checkout URL.
 *
 * @param $order
 *   The order object specified by the checkout URL.
 * @param $checkout_page
 *   The checkout page array specified by the checkout URL.
 *
 * @see commerce_checkout_router()
 */
function hook_commerce_checkout_router($order, $checkout_page) {
  global $user;

  // Redirect anonymous users to a custom login page instructing them to login
  // prior to checkout. (Note that Drupal Commerce does not require users to
  // login prior to checkout as an e-commerce best practice.)
  if (!$user->uid) {
    drupal_set_message(t('Please login or create an account now to continue checkout.'));
    drupal_goto('checkout/login/' . $order->order_id);
  }
}

/**
 * Allows modules to perform business logic when an order completes checkout.
 *
 * This hook coincides with the "Customer completes checkout" event. Only
 * business logic should be performed when this is invoked, such as updating the
 * order status, assigning the order to a user account, or sending notification
 * e-mails. Interaction with the user should instead occur through checkout
 * panes on the checkout completion page.
 *
 * @param $order
 *   The order that just completed checkout.
 */
function hook_commerce_checkout_complete($order) {
  // No example.
}

/**
 * Allows modules to define additional checkout pages.
 *
 * @see http://www.drupalcommerce.org/specification/info-hooks/checkout
 * @see commerce_checkout_commerce_checkout_page_info()
 */
function hook_commerce_checkout_page_info() {

  // Define an additional checkout page.
  $checkout_pages['additional_checkout_page'] = array(
    'title' => t('Additional Checkout Page'),
    'help' => t('Somebody asked for this additional checkout page'),
    'weight' => 60,

    // 'status_cart' => TRUE,

    'back_value' => t('Back'), // Value of the "Back" button
    'submit_value' => t('Next'), // Value of the "Next" button

    // If 'buttons' is FALSE, the "next" and "previous" buttons will be omitted.
    // 'buttons' => FALSE,
  );

  return $checkout_pages;
}

/**
 * Allows modules to declare their own checkout panes.
 *
 * This hook provides a set of parameters defining a pane that can be added
 * to the checkout system. This includes detail information like the title
 * and also a way to determine the names of the key form functions to be called.
 *
 * @see commerce_order_commerce_checkout_pane_info()
 * @see http://www.drupalcommerce.org/specification/info-hooks/checkout
 */
function hook_commerce_checkout_pane_info() {

  $checkout_panes['new_pane'] = array(
    'title' => t('A new pane'),

    // The base of the form builder and related callbacks used to describe the
    // pane. In this case these form functions will be required:
    // - mymodule_new_pane_checkout_form($form, &$form_state, $checkout_pane, $order)
    // - mymodule_new_pane_review($form, $form_state, $checkout_pane, $order)
    // - mymodule_new_pane_settings_form($checkout_pane)
    'base' => 'mymodule_new_pane',

    // Note that the form builder functions and callbacks could also be
    // specified in
    // 'callbacks' => array(
    //   'settings_form' => 'mymodule_new_pane_settings_form',
    //   'checkout_form' => 'mymodule_new_pane_checkout_form',
    //   'checkout_form_validate' => 'mymodule_new_pane_checkout_form_validate',
    //   'checkout_form_submit' => 'mymodule_new_pane_checkout_form_submit',
    //   'review' => 'mymodule_new_pane_review',
    // ),

    // page name where this pane should appear. Defaults to 'checkout'
    'page' => 'checkout',
    'weight' => -5,

    // Administrative title of the pane,  used in administrative interface.
    'name' => t('Additional pane'),

    // 'pane_id' => 'new_pane',
    'collapsible' => TRUE,
    'collapsed' => FALSE,
    'enabled' => TRUE, // Defaults to TRUE, but could default to FALSE.
    'review' => TRUE, // If FALSE will be excluded from review page.

    // A file where the callback functions may be found.
    'file' => 'includes/mymodule.checkout_pane.inc',
  );

  return $checkout_panes;
}

