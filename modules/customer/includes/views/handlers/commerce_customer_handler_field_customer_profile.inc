<?php

/**
 * @file
 * Contains the basic customer profile field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a profile.
 */
class commerce_customer_handler_field_customer_profile extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);

    if (!empty($this->options['link_to_profile'])) {
      $this->additional_fields['profile_id'] = 'profile_id';
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['link_to_profile'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide the link to profile option.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['link_to_profile'] = array(
      '#title' => t("Link this field to the profile's administrative view page"),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_profile']),
    );
  }

  /**
   * Render whatever the data is as a link to the customer profile.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_profile']) && $data !== NULL && $data !== '') {
      $profile_id = $this->get_value($values, 'profile_id');
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'admin/commerce/customer-profiles/' . $profile_id;
    }

    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
