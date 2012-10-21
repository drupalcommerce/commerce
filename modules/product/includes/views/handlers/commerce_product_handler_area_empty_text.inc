<?php

/**
 * Area handler to display the empty text message for products.
 */
class commerce_product_handler_area_empty_text extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Define an option to link to a product add form.
    $options['add_path'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['add_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to a product add form'),
      '#description' => t('Provide the path to a product add form to link to in the empty message. If blank, no link will be included.'),
      '#default_value' => $this->options['add_path'],
    );
  }

  public function render($empty = FALSE) {
    // If the View contains exposed filter input, the empty message indicates
    // no product matched the search criteria.
    $exposed_input = $this->view->get_exposed_input();

    if (!empty($exposed_input)) {
      return t('No products match your search criteria.');
    }

    // Otherwise display the empty text indicating no products have been created
    // yet and provide a link to the add form if configured.
    if (!empty($this->options['add_path'])) {
      return t('No products have been created yet. <a href="!url">Add a product</a>.', array('!url' => url($this->options['add_path'])));
    }
    else {
      return t('No products have been created yet.');
    }
  }
}
