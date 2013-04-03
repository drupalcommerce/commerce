<?php

/**
 * Field handler to present an add to cart form for the product..
 */
class commerce_cart_handler_field_add_to_cart_form extends views_handler_field_entity {

  function option_definition() {
    $options = parent::option_definition();

    $options['show_quantity'] = array('default' => FALSE);
    $options['default_quantity'] = array('default' => 1);
    $options['combine'] = array('default' => TRUE);
    $options['display_path'] = array('default' => FALSE);
    $options['line_item_type'] = array('product' => t('Product'));

    return $options;
  }

  /**
   * Provide the add to cart display options.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['show_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display a textfield quantity widget on the add to cart form.'),
      '#default_value' => $this->options['show_quantity'],
    );

    $form['default_quantity'] = array(
      '#type' => 'textfield',
      '#title' => t('Default quantity'),
      '#default_value' => $this->options['default_quantity'] <= 0 ? 1 : $this->options['default_quantity'],
      '#element_validate' => array('commerce_cart_field_formatter_settings_form_quantity_validate'),
      '#size' => 16,
    );

    $form['combine'] = array(
      '#type' => 'checkbox',
      '#title' => t('Attempt to combine like products on the same line item in the cart.'),
      '#description' => t('The line item type, referenced product, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $this->options['combine'],
    );

    // Add a conditionally visible line item type element.
    $types = commerce_product_line_item_types();

    if (count($types) > 1) {
      $form['line_item_type'] = array(
        '#type' => 'select',
        '#title' => t('Add to Cart line item type'),
        '#options' => array_intersect_key(commerce_line_item_type_get_name(), drupal_map_assoc($types)),
        '#default_value' => !empty($this->options['line_item_type']) ? $this->options['line_item_type'] : 'product',
      );
    }
    else {
      $form['line_item_type'] = array(
        '#type' => 'hidden',
        '#value' => key($types),
      );
    }

    if ($this->view->display[$this->view->current_display]->display_plugin == 'page') {
      $title = t("Link products added to the cart from this page display to the View's path.");
    }
    else {
      $title = t('Link products added to the cart from this display to the current path the customer is viewing where the View is rendered.');
    }

    $form['display_path'] = array(
      '#type' => 'checkbox',
      '#title' => $title,
      '#default_value' => $this->options['display_path'],
    );
  }

  function render($values) {
    // Attempt to load the specified product.
    $product = $this->get_value($values);

    if (!empty($product)) {
      // Extract a default quantity for the Add to Cart form line item.
      $default_quantity = $this->options['default_quantity'] <= 0 ? 1 : $this->options['default_quantity'];
      $product_ids = array($product->product_id);

      // Build the line item we'll pass to the Add to Cart form.
      $line_item = commerce_product_line_item_new($product, $default_quantity, 0, array(), $this->options['line_item_type']);
      $line_item->data['context']['product_ids'] = $product_ids;
      $line_item->data['context']['add_to_cart_combine'] = $this->options['combine'];

      // Generate a form ID for this add to cart form.
      $form_id = commerce_cart_add_to_cart_form_id($product_ids, $default_quantity);

      // Add the display path to the line item's context data array if specified.
      if ($this->options['display_path']) {
        if ($this->view->display[$this->view->current_display]->display_plugin == 'page') {
          $line_item->data['context']['display_path'] = $this->view->display[$this->view->current_display]->display_options['path'];
        }
        else {
          $line_item->data['context']['display_path'] = current_path();
        }
      }

      // Store the View data in the context data array as well.
      $line_item->data['context']['view'] = array(
        'view_name' => $this->view->name,
        'display_name' => $this->view->current_display,
        'human_name' => $this->view->human_name,
        'page' => $this->view->current_page,
      );

      // Build the Add to Cart form using the prepared values.
      $form = drupal_get_form($form_id, $line_item, $this->options['show_quantity'], array());

      return drupal_render($form);
    }
  }
}
