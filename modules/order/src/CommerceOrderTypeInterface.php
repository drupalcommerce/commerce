<?php

/**
 * @file
 * Contains \Drupal\commerce_order\CommerceOrderTypeInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\CommerceEntityTypeInterface;

/**
 * Provides an interface defining a commerce order type entity.
 */
interface CommerceOrderTypeInterface extends CommerceEntityTypeInterface {

  /**
   * Returns the order type description.
   *
   * @return string
   *   The order type description.
   */
  public function getDescription();

  /**
   * Sets the description of the order type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the number of order entities existing with this type.
   *
   * @return int
   */
  public function getOrderCount();

}
