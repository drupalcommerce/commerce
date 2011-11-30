<?php

/**
 * @file
 * Hooks provided by the Order module.
 */


/**
 * Defines order states for use in grouping order statuses together.
 *
 * An order state is a particular phase in the life-cycle of an order that is
 * comprised of one or more order statuses. In that regard, an order state is
 * little more than a container for order statuses with a default status per
 * state. This is useful for categorizing orders and advancing orders from one
 * state to the next without needing to know the particular status an order will
 * end up in.
 *
 * The Order module defines several order states in its own implementation of
 * this hook, commerce_order_commerce_order_state_info():
 * - Canceled: for orders that have been canceled through some user action
 * - Pending: for rrders that have been created and are awaiting further action
 * - Completed: for orders that have been completed as far as the customer
 *   should be concerned.
 *
 * Additionally, the Cart and Checkout modules define the following order states:
 * - Shopping cart: for orders that have not been completed by the customer yet
 * - Checkout: for orders thathave begun but not completed the checkout process
 *
 * The order state array structure is as follows:
 * - name: machine-name identifying the order state using lowercase alphanumeric
 *   characters, -, and _
 * - title: the translatable title of the order state, used in administrative
 *   interfaces
 * - description: a translatable description of the types of orders that would
 *   be in this state
 * - weight: integer weight of the state used for sorting lists of order states;
 *   defaults to 0
 * - default_status: name of the default order status for this state
 *
 * @return
 *   An array of order state arrays keyed by name.
 */
function hook_commerce_order_state_info() {
  $order_states = array();

  $order_states['completed'] = array(
    'name' => 'completed',
    'title' => t('Completed'),
    'description' => t('Orders in this state have been completed as far as the customer is concerned.'),
    'weight' => 10,
    'default_status' => 'completed',
  );

  return $order_states;
}

/**
 * Allows modules to alter the order state definitions of other modules.
 *
 * @param $order_states
 *   An array of order states defined by enabled modules.
 *
 * @see hook_commerce_order_state_info()
 */
function hook_commerce_order_state_info_alter(&$order_states) {
  $order_states['completed']['weight'] = 9;
}

/**
 * Defines order statuses for use in managing orders.
 *
 * An order status is a single step in the life-cycle of an order that
 * administrators can use to know at a glance what has occurred to the order
 * already and/or what the next step in processing the order will be.
 *
 * The Order module defines several order statuses in its own implementation of
 * this hook, commerce_order_commerce_order_status_info():
 * - Canceled: default status of the Canceled state; used for orders that are
 *   marked as canceled via the administrative user interface
 * - Pending: default status of the Pending state; used to indicate the order
 *   has completed checkout and is awaiting further action before being
 *   considered complete
 * - Processing: additional status for the Pending state; used to indicate
 *   orders that have begun to be processed but are not yet completed
 * - Completed: default status of the Completed state; used for orders that
 *   don’t require any further attention or customer interaction
 *
 * The Cart and Checkout modules also define order statuses and interact with
 * them in special ways. The Cart module actually uses the order status to
 * identify an order as a user’s shopping cart order based on the special
 * 'cart' property of order statuses.
 *
 * The Checkout module uses the order status to determine which page of the
 * checkout process a customer is currently on when they go to the checkout URL.
 * As the order progresses through checkout, the order status is updated to
 * reflect the new page. The statuses defined for these things are as follows:
 * - Shopping cart: default status of the Shopping cart state; used for orders
 *   that are pure shopping cart orders that have not begun the checkout
 *   process at all.
 * - Checkout: [page name]: each checkout page has a related order status
 *   containing the name of the checkout page the order has progressed to;
 *   orders in this status are either in checkout or have been abandoned at the
 *   indicated step of the checkout process
 *
 * The order status array structure is as follows:
 * - name: machine-name identifying the order status using lowercase
 *   alphanumeric characters, -, and _
 * - title: the translatable title of the order status, used in administrative
 *   interfaces
 * - state: the name of the order state the order status belongs to
 * - cart: TRUE or FALSE indicating whether or not orders with this status
 *   should be considered shopping cart orders
 * - weight: integer weight of the status used for sorting lists of order
 *   statuses; defaults to 0
 * - status: TRUE or FALSE indicating the enabled status of this order status,
 *   with disabled statuses not being available for use; defaults to TRUE
 *
 * @return
 *   An array of order status arrays keyed by name.
 */
function hook_commerce_order_status_info() {
  $order_statuses = array();

  $order_statuses['completed'] = array(
    'name' => 'completed',
    'title' => t('Completed'),
    'state' => 'completed',
  );

  return $order_statuses;
}

/**
 * Allows modules to alter the order status definitions of other modules.
 *
 * @param $order_statuses
 *   An array of order statuses defined by enabled modules.
 *
 * @see hook_commerce_order_status_info()
 */
function hook_commerce_order_status_info_alter(&$order_statuses) {
  $order_statuses['completed']['title'] = t('Finished');
}

/**
 * Allows modules to specify a uri for an order.
 *
 * When this hook is invoked, the first returned uri will be used for the order.
 * Thus to override the default value provided by the Order UI module, you would
 * need to adjust the order of hook invocation via hook_module_implements_alter()
 * or your module weight values.
 *
 * @param $order
 *   The order object whose uri is being determined.
 *
 * @return
 *  The uri elements of an entity as expected to be returned by entity_uri()
 *  matching the signature of url().
 *
 * @see commerce_order_uri()
 * @see hook_module_implements_alter()
 * @see entity_uri()
 * @see url()
 */
function hook_commerce_order_uri($order) {
  // No example.
}

/**
 * Allows you to prepare order data before it is saved.
 *
 * @param $order
 *   The order object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_order_presave($order) {
  // No example.
}
