<?php

/**
 * @file
 * Contains \Drupal\commerce_order\CommerceOrderTypeInterface.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a commerce order type entity.
 */
interface CommerceOrderTypeInterface extends ConfigEntityInterface {
  /**
   * Returns the number of order entities existing with this type.
   *
   * @return int
   */
  public function getOrderCount();
}
