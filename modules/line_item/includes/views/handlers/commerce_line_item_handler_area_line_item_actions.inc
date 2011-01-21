<?php
// $Id$

/**
 * Defines a line item actions area handler so the action buttons can be plugged
 * into the View itself.
 */
class commerce_line_item_handler_area_line_item_actions extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    // Define an option to control the buttons displayed in the summary.
    $options['buttons'] = array('default' => array());

    foreach (commerce_line_item_actions(array('#submit' => array())) as $name => $button) {
      $options['buttons']['default'][$name] = 0;
    }

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Don't display a form element for the undefined empty option.
    unset($form['empty']);

    // Add checkboxes for the summary buttons if any are available.
    $options = array();

    foreach (commerce_line_item_actions(array('#submit' => array())) as $name => $button) {
      $options[$name] = $button['#value'];
    }

    if (!empty($options)) {
      $form['buttons'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Buttons'),
        '#description' => t('Select the buttons you want to appear beneath the summary.'),
        '#options' => $options,
        '#default_value' => $this->options['buttons'],
      );
    }
  }

  function render($form, $empty = FALSE) {
    if (!$empty) {
      $buttons = array();

      foreach (commerce_line_item_actions(array('#submit' => array())) as $name => $button) {
        if ($this->options['buttons'][$name] === $name) {
          $buttons[$name] = '<!--post-commerce-line-item-button-' . $name . '-->';
        }
      }

      $variables = array(
        'buttons' => $buttons,
      );

      return theme('commerce_line_item_actions', $variables);
    }
  }

}
