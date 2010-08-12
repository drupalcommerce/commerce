<?php
// $Id$

/**
 * @file
 * Checkout pane callback functions for the checkout module.
 */


/**
 * Checkout pane callback: returns a pane allowing the customer to review the
 *   details of the order.
 */
function commerce_checkout_review_pane_checkout_form($pane_values, $checkout_pane, $order) {
  $form = array();

  // Otherwise add any enabled checkout panes that are visible on review.
  $form['review'] = array(
    '#theme' => 'commerce_checkout_review',
    '#data' => array(),
	);

  // Load all the checkout panes enabled for the review page.
  $checkout_panes = commerce_checkout_panes(array('enabled' => TRUE, 'review' => TRUE));

  // Loop through all the pages before the review page...
  foreach (commerce_checkout_pages() as $page_id => $checkout_page) {
		// Exit the loop once the review page is reached.
		if ($page_id == 'review') {
			break;
		}

    // Then loop through all the panes...
    foreach (commerce_checkout_panes(array('page' => $page_id)) as $pane_id => $checkout_pane_local) {
		  // If the pane is on the review callback...
      if ($callback = commerce_checkout_pane_callback($checkout_pane_local, 'review')) {
        // Add a row for it in the review data.
        $form['review']['#data'][$pane_id] = array(
          'title' => $checkout_pane_local->title,
        );

        // Get the review data for this pane.
        $form['review']['#data'][$pane_id]['data'] = $callback($pane_values, $order);
      }
    }
	}

	return $form;
}
