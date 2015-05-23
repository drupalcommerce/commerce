<?php

/**
 * @file
 * Contains \Drupal\commerce_order\OrderTypeInterface.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a commerce order type entity.
 */
interface OrderTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the order type description.
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

}
