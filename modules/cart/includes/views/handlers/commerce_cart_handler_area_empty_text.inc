<?php

/**
 * Area handler to display the empty text message for shopping carts.
 */
class commerce_cart_handler_area_empty_text extends views_handler_area {

  function render($empty = FALSE) {
    // Empty shopping cart Views pages (and any variant of) will output the
    // theme for empty shopping cart pages.
    if ($this->view->display_handler instanceOf views_plugin_display_page) {
      $theme_hook = 'commerce_cart_empty_page';
    }

    // All other display handlers (that includes blocks, attachments, content
    // panes, etc.) will fallback to using the block variant of the empty
    // shopping cart theme.
    else {
      $theme_hook = 'commerce_cart_empty_block';
    }

    return theme($theme_hook);
  }
}
