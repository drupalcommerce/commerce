<?php

/**
 * Defines a line item actions area handler so the action buttons can be plugged
 * into the View itself.
 */
class commerce_line_item_handler_area_line_item_actions extends views_handler_area {

  function option_definition() {
    $options = parent::option_definition();

    // Undefine the empty option.
    unset($options['empty']);

    return $options;
  }

  function render($empty = FALSE) {
    if (!$empty) {
      $variables = array(
        'buttons' => '<!--post-commerce-line-item-buttons-->',
      );

      return theme('commerce_line_item_actions', $variables);
    }
  }

}
