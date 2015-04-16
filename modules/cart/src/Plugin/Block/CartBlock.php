<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Plugin\Block\CartBlock.
 */

namespace Drupal\commerce_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Shopping cart block.
 *
 * @Block(
 *   id = "cart",
 *   admin_label = @Translation("Shopping Cart"),
 *   category = @Translation("Commerce")
 * )
 */
class CartBlock extends BlockBase {

  /**
   * Outputs the cart block views for each non-empty cart belonging
   * to the current user.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $build = [];
    // Use the cart provider to get the carts.
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $carts = $cart_provider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      return $cart->hasLineItems();
    });
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {
        $build[$cart_id] = [
          '#prefix' => '<div class="cart cart-block">',
          '#suffix' => '</div>',
          '#type' => 'view',
          '#name' => $cart_views[$cart_id],
          '#arguments' => [$cart_id],
          '#embed' => TRUE,
        ];
      }
    }
    else {
      $build['empty'] = [
        '#prefix' => '<div class="cart-empty-block">',
        '#markup' => $this->t('Your shopping cart is empty.'),
        '#suffix' => '</div.',
      ];
    }

    return $build;
  }

  /**
   * Gets the cart views for each cart.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The cart orders.
   *
   * @return array
   *   An array of view ids keyed by cart order id.
   */
  protected function getCartViews(array $carts) {
    $order_type_ids = array_map(function($cart) {
      return $cart->bundle();
    }, $carts);
    $order_type_storage = \Drupal::entityManager()->getStorage('commerce_order_type');
    $order_types = $order_type_storage->loadMultiple(array_unique($order_type_ids));

    foreach ($order_type_ids as $cart_id => $order_type_id) {
      $order_type = $order_types[$order_type_id];
      $cart_views[$cart_id] = $order_type->getThirdPartySetting('commerce_cart', 'cart_block_view', 'commerce_cart_block');
    }

    return $cart_views;
  }

}
