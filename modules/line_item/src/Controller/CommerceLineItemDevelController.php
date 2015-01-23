<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceLineItemDevelController.
 */

namespace Drupal\commerce_line_item\Controller;

use Drupal\commerce_line_item\CommerceLineItemInterface;
use Drupal\commerce_line_item\CommerceLineItemTypeInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Line item devel routes.
 */
class CommerceLineItemDevelController extends DevelController {

  /**
   * Dump devel information for a Commerce Line item Type.
   *
   * @param \Drupal\commerce_line_item\CommerceLineItemTypeInterface $lineItemType
   *
   * @return string
   */
  public function lineItemTypeLoad(CommerceLineItemTypeInterface $lineItemType) {
    return $this->loadObject('line_item_type', $lineItemType);
  }

  /**
   * Dump devel information for a Commerce Line item.
   *
   * @param \Drupal\commerce_line_item\CommerceLineItemInterface $lineItem
   *
   * @return string
   */
  public function lineItemLoad(CommerceLineItemInterface $lineItem) {
    return $this->loadObject('line_item', $lineItem);
  }

}
