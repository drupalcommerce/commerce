<?php
// $Id$

/**
 * @file
 * Contains the basic order field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to an order.
 */
class commerce_order_handler_field_commerce_order_order extends views_handler_field {
  function init(&$view, $options) {
    parent::init($view, $options);

    if (!empty($this->options['link_to_order'])) {
      $this->additional_fields['order_id'] = 'order_id';
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['link_to_order'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide the link to order option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['link_to_order'] = array(
      '#title' => t("Link this field to the order's administrative view page"),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_order']),
    );
  }

  /**
   * Render whatever the data is as a link to the order.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_order']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'admin/commerce/orders/' . $values->{$this->aliases['order_id']};
    }

    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
