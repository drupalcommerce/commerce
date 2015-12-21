<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartProviderInterface.
 */

namespace Drupal\commerce_cart;

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
   *   The order type id.
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
   * Gets the cart order for the given store and user.
   *
   * @param string $order_type
   *   The order type id.
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
   * Gets the cart order id for the given store and user.
   *
   * @param string $order_type
   *   The order type id.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return int|null
   *   The cart order id, or NULL if none found.
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

}
