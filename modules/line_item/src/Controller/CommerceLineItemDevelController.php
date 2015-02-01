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
   * @param \Drupal\commerce_line_item\CommerceLineItemTypeInterface $commerce_line_item_type
   *
   * @return string
   */
  public function lineItemTypeLoad(CommerceLineItemTypeInterface $commerce_line_item_type) {
    return $this->loadObject('line_item_type', $commerce_line_item_type);
  }

  /**
   * Dump devel information for a Commerce Line item.
   *
   * @param \Drupal\commerce_line_item\CommerceLineItemInterface $commerce_line_item
   *
   * @return string
   */
  public function lineItemLoad(CommerceLineItemInterface $commerce_line_item) {
    return $this->loadObject('line_item', $commerce_line_item);
  }

}
