<?php

/**
 * Area handler to display the empty text message for orders.
 */
class commerce_order_handler_area_empty_text extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    // Define an option to link to an order creation form.
    $options['add_path'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Don't display a form element for the undefined empty option.
    unset($form['empty']);

    $form['add_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to an order creation form'),
      '#description' => t('Provide the path to an order creation form to link to in the empty message. If blank, no link will be included.'),
      '#default_value' => $this->options['add_path'],
    );
  }

  public function render($empty = FALSE) {
    // If the View contains exposed filter input, the empty message indicates
    // no orders matched the search criteria.
    $exposed_input = $this->view->get_exposed_input();

    if (!empty($exposed_input)) {
      return t('No orders match your search criteria.');
    }

    // Otherwise display the empty text indicating no orders have been created
    // yet and provide a link to the creation form if configured.
    if (!empty($this->options['add_path'])) {
      return t('No orders have been created yet. <a href="!url">Create an order</a>.', array('!url' => url($this->options['add_path'])));
    }
    else {
      return t('No orders have been created yet.');
    }
  }
}
