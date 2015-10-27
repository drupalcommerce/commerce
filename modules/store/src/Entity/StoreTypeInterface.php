<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Entity\StoreTypeInterface.
 */

namespace Drupal\commerce_store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for store types.
 */
interface StoreTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the store type description.
   *
   * @return string
   *   The store type description.
   */
  public function getDescription();

  /**
   * Sets the store type description.
   *
   * @param string $description
   *   The store type description.
   *
   * @return $this
   */
  public function setDescription($description);

}
