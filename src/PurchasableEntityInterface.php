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

  /**
   * Gets the purchasable entity's line item type.
   *
   * Used for finding/creating the appropriate line item when purchasing a
   * product (adding it to an order).
   *
   * @return string
   *   The line item type.
   */
  public function getLineItemType();

  /**
   * Gets the purchasable entity's line item title.
   *
   * Saved in the $lineItem->title field to protect the line items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The line item title.
   */
  public function getLineItemTitle();

}
