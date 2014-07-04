<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceDevelController.
 */

namespace Drupal\commerce\Controller;

use Drupal\commerce\CommerceStoreInterface;
use Drupal\commerce\CommerceStoreTypeInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Store devel routes.
 */
class CommerceDevelController extends DevelController {

  /**
   * Dump devel information for a Commerce Store Type.
   *
   * @param \Drupal\commerce\CommerceStoreTypeInterface $commerce_store_type
   *
   * @return string
   */
  public function storeTypeLoad(CommerceStoreTypeInterface $commerce_store_type) {
    return $this->loadObject('commerce_store_type', $commerce_store_type);
  }

  /**
   * Dump devel information for a Commerce Store.
   *
   * @param \Drupal\commerce\CommerceStoreInterface $commerce_store
   *
   * @return string
   */
  public function storeLoad(CommerceStoreInterface $commerce_store) {
    return $this->loadObject('commerce_store', $commerce_store);
  }
}
