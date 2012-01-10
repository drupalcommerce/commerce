<?php

/**
 * Defines an area handler that displays a "View order" form allowing the user
 * to enter an order number and redirect to that order's view page.
 */
class commerce_order_ui_handler_area_view_order_form extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    // Define an option to control the order identifier and redirect page.
    $options['identifier'] = array('default' => 'order_number');
    $options['redirect_page'] = array('default' => 'admin');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Don't display a form element for the undefined empty option.
    unset($form['empty']);

    $form['identifier'] = array(
      '#type' => 'radios',
      '#title' => t('Order identifier used for redirection'),
      '#options' => array(
        'order_number' => t('Order number', array(), array('context' => 'a drupal commerce order')),
        'order_id' => t('Order ID', array(), array('context' => 'a drupal commerce order')),
        'select' => t('Let the user select'),
      ),
      '#default_value' => $this->options['identifier'],
    );

    $form['redirect_page'] = array(
      '#type' => 'radios',
      '#title' => t('Redirect to which order view page'),
      '#options' => array(
        'admin' => t('Admin page'),
        'customer' => t('Customer page'),
        'select' => t('Let the user select'),
      ),
      '#default_value' => $this->options['redirect_page'],
    );
  }

  function render($empty = FALSE) {
    $form = drupal_get_form('commerce_order_ui_redirect_form', $this->options['redirect_page'], $this->options['identifier']);
    return drupal_render($form);
  }
}
