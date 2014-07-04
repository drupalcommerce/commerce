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
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerce_order_type
   *
   * @return string
   */
  public function orderTypeLoad(CommerceOrderTypeInterface $commerce_order_type) {
    return $this->loadObject('commerce_order_type', $commerce_order_type);
  }

  /**
   * Dump devel information for a Commerce Order.
   *
   * @param \Drupal\commerce_order\CommerceOrderInterface $commerce_order
   *
   * @return string
   */
  public function orderLoad(CommerceOrderInterface $commerce_order) {
    return $this->loadObject('commerce_order', $commerce_order);
  }
}
