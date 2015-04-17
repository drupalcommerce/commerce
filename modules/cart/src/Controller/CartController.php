<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Controller\CartController.
 */

namespace Drupal\commerce_cart\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for cart routes.
 */
class CartController extends ControllerBase {

  /**
   * Displays the shopping cart form and associated information.
   *
   * @return array
   *   A view with cart form content.
   */
  public function cartPage() {
    // Get the Shopping Order id,
    $cart_order_id = 1;
    // To do : use the Cart settings to use a custom View for this page.
    return views_embed_view('commerce_cart_form', 'default', [$cart_order_id]);
  }

}
