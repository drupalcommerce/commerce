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
 * Defines checkout pages available for use in the checkout process.
 *
 * The checkout form is not a true multi-step form in the Drupal sense, but it
 * does use a series of connected menu items and the same form builder function
 * to present the contents of each checkout page. Furthermore, as the customer
 * progresses through checkout, their order’s status will be updated to reflect
 * their current page in checkout.
 *
 * The Checkout module defines several checkout pages in its own implementation
 * of this hook, commerce_checkout_commerce_checkout_page_info():
 * - Checkout: the first page where the customer will enter their basic order
 *   information
 * - Review: a page where they can verify that the details of their order are
 *   correct (and the default location of the payment checkout pane if the
 *   Payment module is enabled)
 * - Complete - the final step in checkout displaying pertinent order details
 *   and links
 *
 * The Payment module adds an additional page:
 * - Payment: a page that only appears when the customer selected an offsite
 *   payment method; the related checkout pane handles building the form and
 *   automatically submitting it to send the customer to the payment provider
 *
 * The checkout page array contains properties that define how the page should
 * interact with the shopping cart and order status systems. It also contains
 * properties that define the appearance and use of buttons on the page.
 *
 * The checkout page array structure is as follows:
 * - page_id: machine-name identifying the page using lowercase alphanumeric
 *   characters, -, and _
 * - title: the Drupal page title used for this checkout page
 * - name: the translatable name of the page, used in administrative displays
 *   and the page’s corresponding order status; if not specified, defaults to
 *   the title
 * - help: the translatable help text displayed in a .checkout-help div at the
 *   top of the checkout page (defined as part of the form array, not displayed
 *   via hook_help())
 * - weight: integer weight of the page used for determining the page order;
 *   populated automatically if not specified
 * - status_cart: boolean indicating whether or not this page’s corresponding
 *   order status should be considered a shopping cart order status (this is
 *   necessary because the shopping cart module relies on order status to
 *   identify the user’s current shopping cart); defaults to TRUE
 * - buttons - boolean indicating whether or not the checkout page should have
 *   buttons for continuing and going back in the checkout process; defaults to
 *   TRUE
 * - back_value: the translatable value of the submit button used for going back
 *   in the checkout process; defaults to ‘Back’
 * - submit_value: the translatable value of the submit button used for going
 *   forward in the checkout process; defaults to ‘Continue’
 * - prev_page: the page_id of the previous page in the checkout process; should
 *   not be set by the hook but will be populated automatically when the page is
 *   loaded
 * - next_page: the page_id of the next page in the checkout process; should not
 *   be set by the hook but will be populated automatically when the page is
 *   loaded
 *
 * Note: At this point there is no way to add checkout pages via the UI, so
 * sites wishing to add extra steps to the checkout process will need to define
 * custom pages.
 *
 * @return
 *   An array of checkout page arrays keyed by page_id.
 */
function hook_commerce_checkout_page_info() {
  $checkout_pages = array();

  $checkout_pages['complete'] = array(
    'name' => t('Complete'),
    'title' => t('Checkout complete'),
    'weight' => 50,
    'status_cart' => FALSE,
    'buttons' => FALSE,
  );

  return $checkout_pages;
}

/**
 * Allows modules to alter checkout pages defined by other modules.
 *
 * @param $checkout_pages
 *   The array of checkout page arrays.
 *
 * @see hook_commerce_checkout_page_info()
 */
function hook_commerce_checkout_page_info_alter(&$checkout_pages) {
  $checkout_pages['review']['weight'] = 15;
}

/**
 * Defines checkout panes available for use on checkout pages.
 *
 * Any number of panes may be assigned to a page and reordered using the
 * checkout form builder. Each pane may also have its own settings form
 * accessible from the builder. On the checkout page, a pane is represented as a
 * fieldset or container div. Panes possess a variety of callbacks used to
 * define settings and checkout form elements and validate / process submitted
 * data when the checkout form is submitted.
 *
 * The Checkout module defines a couple of checkout panes in its own
 * implementation of this hook, commerce_checkout_commerce_checkout_pane_info():
 * - Review: the main pane on the default Review page that displays details from
 *   other checkout panes for the user to review prior to completion
 * - Completion message: the main pane on the default Complete page that
 *   displays the checkout completion message and links
 *
 * Other checkout panes are defined by the Cart, Customer, and Payment modules
 * as follows:
 * - Shopping cart contents: displays a View listing the contents of the
 *   shopping cart order with a summary including the total cost and number of
 *   items but no links (as used in the cart block)
 * - Customer profile panes: the Customer module defines one for each type of
 *   customer information profile using the name of the profile type as the
 *   title of the pane
 * - Payment: the main payment pane that lets the customer select a payment
 *   method and supply any necessary payment details; appears on the Review page
 *   beneath the Review pane by default, allowing payments to be processed
 *   immediately on submission for security purposes
 * - Off-site payment redirect: a pane that handles redirected payment services
 *   with some specialized behavior; should be the only pane on the actual
 *   payment page
 *
 * The checkout pane array contains properties that directly affect the pane’s
 * fieldset display on the checkout form. It also contains a property used to
 * automatically populate an array of callback function names.
 *
 * The full list of properties is as follows:
 * - pane_id: machine-name identifying the pane using lowercase alphanumeric
 *   characters, -, and _
 * - title: the translatable title used for this checkout pane as the fieldset
 *   title in checkout
 * - name: the translatable name of the pane, used in administrative displays;
 *   if not specified, defaults to the title
 * - page: the page_id of the checkout page the pane should appear on by
 *   default; defaults to ‘checkout’
 * - locked: boolean indicating that the pane cannot be moved from the
 *   specified checkout page.
 * - collapsible: boolean indicating whether or not the checkout pane’s fieldset
 *   should be collapsible; defaults to FALSE
 * - collapsed: boolean indicating whether or not the checkout pane’s fieldset
 *   should be collapsed by default; defaults to FALSE
 * - weight: integer weight of the page used for determining the pane sort order
 *   on checkout pages; defaults to 0
 * - enabled: boolean indicating whether or not the pane is enabled by default;
 *   defaults to TRUE
 * - review: boolean indicating whether or not the pane should be included in
 *   the review checkout pane; defaults to TRUE
 * - module: the name of the module that defined the pane; should not be set by
 *   the hook but will be populated automatically when the pane is loaded
 * - file: the filepath of an include file relative to the pane’s module
 *   containing the callback functions for this pane, allowing modules to store
 *   checkout pane code in include files that only get loaded when necessary
 *   (like the menu item file property)
 * - base: string used as the base for the magically constructed callback names,
 *   each of which will be defaulted to [base]_[callback] unless explicitly set;
 *   defaults to the pane_id
 * - callbacks: an array of callback function names for the various types of
 *   callback required for all the checkout pane operations, arguments per
 *   callback in parentheses:
 *   - settings_form($checkout_pane): returns form elements for the pane’s
 *     settings form
 *   - checkout_form($form, &$form_state, $checkout_pane, $order): returns form
 *     elements for the pane’s checkout form fieldset
 *   - checkout_form_validate($form, &$form_state, $checkout_pane, $order):
 *     validates data inputted via the pane’s elements on the checkout form and
 *     must return TRUE or FALSE indicating whether or not all the data validated
 *   - checkout_form_submit($form, &$form_state, $checkout_pane, $order):
 *     processes data inputted via the pane’s elements on the checkout form,
 *     often updating parts of the order object based on the data
 *   - review($form, $form_state, $checkout_pane, $order): returns data used in
 *     the construction of the Review checkout pane
 *
 * The helper function commerce_checkout_pane_callback() will include a checkout
 * pane’s include file if specified and check for the existence of a callback,
 * returning either the name of the function or FALSE if the specified callback
 * does not exist for the specified pane.
 *
 * @return
 *   An array of checkout pane arrays keyed by pane_id.
 *
 * @see commerce_checkout_pane_callback()
 */
function hook_commerce_checkout_pane_info() {
  $checkout_panes = array();

  $checkout_panes['checkout_review'] = array(
    'title' => t('Review'),
    'file' => 'includes/commerce_checkout.checkout_pane.inc',
    'base' => 'commerce_checkout_review_pane',
    'page' => 'review',
    'fieldset' => FALSE,
    'locked' => FALSE,
  );

  return $checkout_panes;
}

/**
 * Allows modules to alter checkout panes defined by other modules.
 *
 * @param $checkout_panes
 *   The array of checkout pane arrays.
 *
 * @see hook_commerce_checkout_pane_info()
 */
function hook_commerce_checkout_pane_info_alter(&$checkout_panes) {
  $checkout_panes['billing']['weight'] = -6;
}
