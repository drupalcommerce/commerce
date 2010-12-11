<?php
// $Id$

/**
 * @file
 * Contains the basic product field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a product.
 */
class commerce_product_handler_field_product extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);

    if (!empty($this->options['link_to_product'])) {
      $this->additional_fields['product_id'] = 'product_id';

      if (module_exists('translation')) {
        $this->additional_fields['language'] = array('table' => 'commerce_product', 'field' => 'language');
      }
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['link_to_product'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide the link to product option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['link_to_product'] = array(
      '#title' => t("Link this field to the product's administrative view page"),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_product']),
    );
  }

  /**
   * Render whatever the data is as a link to the product.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_product']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'admin/commerce/products/' . $values->{$this->aliases['product_id']};

      if (isset($this->aliases['language'])) {
        $languages = language_list();

        if (isset($languages[$values->{$this->aliases['language']}])) {
          $this->options['alter']['language'] = $languages[$values->{$this->aliases['language']}];
        }
      }
    }

    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
