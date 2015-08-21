<?php

/**
 * @file
 * Contains \Drupal\commerce\PurchasableEntityInterface.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for purchasable entities.
 *
 * Lives in Drupal\commerce instead of Drupal\commerce_order so that entity
 * type providing modules such as commerce_product don't need to depend
 * on commerce_order.
 */
interface PurchasableEntityInterface extends ContentEntityInterface {
}
