<?php

/**
 * Area handler to display the empty text message for customer profiles.
 */
class commerce_customer_handler_area_empty_text extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    // Define an option to link to a customer profile add form.
    $options['add_path'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Don't display a form element for the undefined empty option.
    unset($form['empty']);

    $form['add_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to a customer profile add form'),
      '#description' => t('Provide the path to a customer profile add form to link to in the empty message. If blank, no link will be included.'),
      '#default_value' => $this->options['add_path'],
    );
  }

  public function render($empty = FALSE) {
    // If the View contains exposed filter input, the empty message indicates
    // no customer profiles matched the search criteria.
    $exposed_input = $this->view->get_exposed_input();

    if (!empty($exposed_input)) {
      return t('No customer profiles match your search criteria.');
    }

    // Otherwise display the empty text indicating no customer profiles have
    // been created yet and provide a link to the add form if configured.
    if (!empty($this->options['add_path'])) {
      return t('No customer profiles have been created yet. <a href="!url">Add a customer profile</a>.', array('!url' => url($this->options['add_path'])));
    }
    else {
      return t('No customer profiles have been created yet.');
    }
  }
}
