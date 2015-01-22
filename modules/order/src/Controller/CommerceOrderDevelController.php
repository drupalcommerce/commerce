<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceOrderDevelController.
 */

namespace Drupal\commerce_order\Controller;

use Drupal\commerce_order\CommerceOrderInterface;
use Drupal\commerce_order\CommerceOrderTypeInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Order devel routes.
 */
class CommerceOrderDevelController extends DevelController {

  /**
   * Dump devel information for a Commerce Order Type.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerceOrderType
   *
   * @return string
   */
  public function orderTypeLoad(CommerceOrderTypeInterface $commerceOrderType) {
    return $this->loadObject('commerce_order_type', $commerceOrderType);
  }

  /**
   * Dump devel information for a Commerce Order.
   *
   * @param \Drupal\commerce_order\CommerceOrderInterface $commerceOrder
   *
   * @return string
   */
  public function orderLoad(CommerceOrderInterface $commerceOrder) {
    return $this->loadObject('commerce_order', $commerceOrder);
  }

}
