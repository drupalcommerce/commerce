<?php
/**
 * @file
 * Contains CommerceDevelController.php
 */

namespace Drupal\commerce\Controller;


use Drupal\commerce\CommerceStoreInterface;
use Drupal\commerce\CommerceStoreTypeInterface;
use Drupal\devel\Controller\DevelController;

class CommerceDevelController extends DevelController {

  public function storeTypeLoad(CommerceStoreTypeInterface $commerce_store_type) {
    return $this->loadObject('commerce_store_type', $commerce_store_type);
  }

  public function storeLoad(CommerceStoreInterface $commerce_store) {
    return $this->loadObject('commerce_store', $commerce_store);
  }
}
