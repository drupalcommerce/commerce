<?php
// $Id$

/**
 * Defines a line item summary area handler so the summary can be plugged into
 *   the View itself.
 */
class commerce_line_item_handler_area_line_item_summary extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    // Define an option to control the links displayed in the summary.
    $options['links'] = array('default' => array('view_cart' => 0, 'checkout' => 0));

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Don't display a form element for the undefined empty option.
    unset($form['empty']);

    // Add checkboxes for the summary links if any are available.
    $options = array();

    if (module_exists('commerce_cart')) {
      $options['view_cart'] = t('View cart');
    }

    if (module_exists('commerce_checkout')) {
      $options['checkout'] = t('Checkout');
    }

    if (!empty($options)) {
      $form['links'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Links'),
        '#description' => t('Select the links you want to appear beneath the summary.'),
        '#options' => $options,
        '#default_value' => $this->options['links'],
      );
    }
  }

  function render($empty = FALSE) {
    if (!$empty) {
      // Construct a pseudo line item reference field array based on the View's
      // results that we'll use to interact with the line item API.
      $line_item_ids = array(LANGUAGE_NONE => array());

      foreach ($this->view->result as $result) {
        if (!empty($result->commerce_line_item_field_data_line_items_line_item_id)) {
          $line_item_ids[LANGUAGE_NONE][] = array('line_item_id' => $result->commerce_line_item_field_data_line_items_line_item_id);
        }
      }

      // Add total information and a link to checkout.
      $quantity = commerce_line_items_quantity($line_item_ids);
      $total = commerce_line_items_total($line_item_ids);
      $currency = commerce_currency_load($total['currency_code']);

      $links = array();

      // Only add the view cart link if the cart is enabled.
      if (module_exists('commerce_cart') && $this->options['links']['view_cart'] === 'view_cart') {
        $links['line-item-summary-view-cart'] = array(
          'title' => t('View cart'),
          'href' => 'cart',
          'attributes' => array('rel' => 'nofollow'),
        );
      }

      // Only add the checkout link if checkout is enabled.
      if (module_exists('commerce_checkout') && $this->options['links']['checkout'] === 'checkout' && user_access('access checkout')) {
        $links['line-item-summary-checkout'] = array(
          'title' => t('Checkout'),
          'href' => 'checkout',
          'attributes' => array('rel' => 'nofollow'),
        );
      }

      // Build the variables array to send to the template.
      $variables = array(
        'view' => $this->view,
        'quantity_raw' => $quantity,
        'quantity_label' => format_plural($quantity, 'item', 'items'),
        'quantity' => format_plural($quantity, '1 item', '@count items'),
        'total_raw' => number_format(commerce_currency_round($total['price'], $currency), $currency->decimals),
        'total_label' => t('Total:'),
        'total' => commerce_currency_format($total['price'], $total['currency_code'], $this->view),
        'links' => theme('links', array('links' => $links)),
      );

      return theme('commerce_line_item_summary', $variables);
    }

    return '';
  }
}
