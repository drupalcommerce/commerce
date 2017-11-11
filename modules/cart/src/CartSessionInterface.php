<?php

namespace Drupal\commerce_cart;

/**
 * Stores cart ids in the anonymous user's session.
 *
 * Allows the system to keep track of which cart orders belong to the anonymous
 * user. The session is the only available storage in this case, since all
 * anonymous users share the same user id (0).
 *
 * Tracks active and completed carts separately.
 *
 * @see \Drupal\commerce_cart\CartProviderInterface
 */
interface CartSessionInterface {

  // The cart session types.
  const ACTIVE = 'active';
  const COMPLETED = 'completed';

  /**
   * Gets all cart order ids from the session.
   *
   * @param string $type
   *   The cart session type.
   *
   * @return int[]
   *   A list of cart orders ids.
   */
  public function getCartIds($type = self::ACTIVE);

  /**
   * Adds the given cart order ID to the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   */
  public function addCartId($cart_id, $type = self::ACTIVE);

  /**
   * Checks whether the given cart order ID exists in the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   *
   * @return bool
   *   TRUE if the given cart order ID exists in the session, FALSE otherwise.
   */
  public function hasCartId($cart_id, $type = self::ACTIVE);

  /**
   * Deletes the given cart order id from the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   */
  public function deleteCartId($cart_id, $type = self::ACTIVE);

}
