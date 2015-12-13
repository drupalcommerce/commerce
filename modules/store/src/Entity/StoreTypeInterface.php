<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Entity\StoreTypeInterface.
 */

namespace Drupal\commerce_store\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\entity\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for store types.
 */
interface StoreTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {
}
