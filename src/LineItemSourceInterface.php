<?php

/**
 * @file
 * Contains \Drupal\commerce\LineItemSourceInterface.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for entity types that can serve as line item sources.
 *
 * Lives in Drupal\commerce instead of Drupal\commerce_order so that entity
 * type providing modules such as commerce_product don't need to depend
 * on commerce_order.
 *
 * @todo Add a getPrice() method.
 */
interface LineItemSourceInterface extends ContentEntityInterface {
}
