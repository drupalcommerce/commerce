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
    // Get the cart order id using the cart provider.
    $cart_order_ids = \Drupal::service('commerce_cart.cart_provider')->getCartIds();
    $cart_order_id = reset($cart_order_ids);
    // Get cart page view from cart settings config.
    $cart_page_view = \Drupal::config('commerce_cart.settings')->get('cart_page.view');
    return views_embed_view($cart_page_view, 'default', $cart_order_id);
  }

}
