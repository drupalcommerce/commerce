<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\CartSessionInterface.
 */

namespace Drupal\commerce_cart;

/**
 * Stores cart ids in the anonymous user's session.
 *
 * Allows the system to keep track of which cart orders belong to the anonymous
 * user. The session is the only available storage in this case, since all
 * anonymous users share the same user id (0).
 *
 * @see \Drupal\commerce_cart\CartProviderInterface
 */
interface CartSessionInterface {

  /**
   * Gets all cart order ids from the session.
   *
   * @return int[]
   *   A list of cart orders ids.
   */
  public function getCartIds();

  /**
   * Adds the given cart order id to the session.
   *
   * @param int $cart_id
   *   The cart order id.
   */
  public function addCartId($cart_id);

  /**
   * Checks whether the given cart order id exists in the session.
   *
   * @param int $cart_id
   *   The cart order id.
   *
   * @return bool
   *   TRUE if the given cart order id exists in the session, FALSE otherwise.
   */
  public function hasCartId($cart_id);

  /**
   * Deletes the given cart order id from the session.
   *
   * @param int $cart_id
   *   The cart order id.
   */
  public function deleteCartId($cart_id);

}
