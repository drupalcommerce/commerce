<?php

/**
 * @file
 * Contains Drupal\commerce_store\StoreTypeInterface.
 */

namespace Drupal\commerce_store;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a store type entity.
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
   * Sets the description of the store type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

}
