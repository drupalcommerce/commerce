<?php

namespace Drupal\commerce_cart;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates and loads carts for anonymous and authenticated users.
 *
 * @see \Drupal\commerce_cart\CartSessionInterface
 */
interface CartProviderInterface {

  /**
   * Creates a cart order for the given store and user.
   *
   * @param string $order_type
   *   The order type ID.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The created cart order.
   *
   * @throws \Drupal\commerce_cart\Exception\DuplicateCartException
   *   When a cart with the given criteria already exists.
   */
  public function createCart($order_type, StoreInterface $store, AccountInterface $account = NULL);

  /**
   * Finalizes the given cart order.
   *
   * This will finalize the cart, under the assumption the order will no longer
   * be treated as a draft order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $cart
   *   The cart order.
   * @param bool $save_cart
   *   Whether the cart should be saved after the operation.
   */
  public function finalizeCart(OrderInterface $cart, $save_cart = TRUE);

  /**
   * Gets the cart order for the given store and user.
   *
   * @param string $order_type
   *   The order type ID.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The cart order, or NULL if none found.
   */
  public function getCart($order_type, StoreInterface $store, AccountInterface $account = NULL);

  /**
   * Gets the cart order ID for the given store and user.
   *
   * @param string $order_type
   *   The order type ID.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return int|null
   *   The cart order ID, or NULL if none found.
   */
  public function getCartId($order_type, StoreInterface $store, AccountInterface $account = NULL);

  /**
   * Gets all cart orders for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   *   A list of cart orders.
   */
  public function getCarts(AccountInterface $account = NULL);

  /**
   * Gets all cart order ids for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return int[]
   *   A list of cart orders ids.
   */
  public function getCartIds(AccountInterface $account = NULL);

  /**
   * Invalidates a cart ID from internal cache.
   *
   * This is used when a cart is finalized and is no longer a cart.
   *
   * @param string $cart_id
   *   The cart order ID.
   *
   * @return $this
   */
  public function invalidateCartId($cart_id);

}
