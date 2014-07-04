<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceStoreTypeInterface.
 */

namespace Drupal\commerce;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a store type entity.
 */
interface CommerceStoreTypeInterface extends ConfigEntityInterface {
  /**
   * How many stores exist with this type ?
   *
   * @return int
   */
  public function getStoreCount();
}
