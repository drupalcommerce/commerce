<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartSession.
 */

namespace Drupal\commerce_cart;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Default implementation of the cart session.
 */
class CartSession implements CartSessionInterface {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new CartSession object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartIds() {
    return $this->session->get('commerce_cart_orders', []);
  }

  /**
   * {@inheritdoc}
   */
  public function addCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    $ids[] = $cart_id;
    $this->session->set('commerce_cart_orders', array_unique($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function hasCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    return in_array($cart_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCartId($cart_id) {
    $ids = $this->session->get('commerce_cart_orders', []);
    $ids = array_diff($ids, [$cart_id]);
    if (!empty($ids)) {
      $this->session->set('commerce_cart_orders', $ids);
    }
    else {
      // Remove the empty list to allow the system to clean up empty sessions.
      $this->session->remove('commerce_cart_orders');
    }
  }

}
