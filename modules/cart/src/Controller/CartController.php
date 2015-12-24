<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Controller\CartController.
 */

namespace Drupal\commerce_cart\Controller;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the cart page.
 */
class CartController extends ControllerBase {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new CartController object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(CartProviderInterface $cart_provider) {
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Outputs a cart view for each non-empty cart belonging to the current user.
   *
   * @return array
   *   A render array.
   */
  public function cartPage() {
    $build = [];
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      return $cart->hasLineItems();
    });
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {
        $build[$cart_id] = [
          '#prefix' => '<div class="cart cart-form">',
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
        '#prefix' => '<div class="cart-empty-page">',
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
    $order_type_storage = $this->entityTypeManager()->getStorage('commerce_order_type');
    $order_types = $order_type_storage->loadMultiple(array_unique($order_type_ids));
    $cart_views = [];
    foreach ($order_type_ids as $cart_id => $order_type_id) {
      $order_type = $order_types[$order_type_id];
      $cart_views[$cart_id] = $order_type->getThirdPartySetting('commerce_cart', 'cart_form_view', 'commerce_cart_form');
    }

    return $cart_views;
  }

}
