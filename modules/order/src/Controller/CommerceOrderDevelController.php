<?php
/**
 * @file
 * Contains CommerceOrderDevelController.php
 */

namespace Drupal\commerce_order\Controller;


use Drupal\commerce_order\CommerceOrderInterface;
use Drupal\commerce_order\CommerceOrderTypeInterface;
use Drupal\devel\Controller\DevelController;

class CommerceOrderDevelController extends DevelController {

  public function orderTypeLoad(CommerceOrderTypeInterface $commerce_order_type) {
    return $this->loadObject('commerce_order_type', $commerce_order_type);
  }

  public function orderLoad(CommerceOrderInterface $commerce_order) {
    return $this->loadObject('commerce_order', $commerce_order);
  }
}
