<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\OrderTypeInterface.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for order types.
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
