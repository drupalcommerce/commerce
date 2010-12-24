<?php
// $Id$

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
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'admin/commerce/customer-profiles/' . $values->{$this->aliases['profile_id']};

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
