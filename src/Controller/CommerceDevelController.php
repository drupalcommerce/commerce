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
   * @param \Drupal\commerce\CommerceStoreTypeInterface $commerceStoreType
   *
   * @return string
   */
  public function storeTypeLoad(CommerceStoreTypeInterface $commerceStoreType) {
    return $this->loadObject('commerce_store_type', $commerceStoreType);
  }

  /**
   * Dump devel information for a Commerce Store.
   *
   * @param \Drupal\commerce\CommerceStoreInterface $commerceStore
   *
   * @return string
   */
  public function storeLoad(CommerceStoreInterface $commerceStore) {
    return $this->loadObject('commerce_store', $commerceStore);
  }

}
