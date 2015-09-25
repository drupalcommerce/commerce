<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Controller\StoreController.
 */

namespace Drupal\commerce_store\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Route controller for stores.
 */
class StoreController extends ControllerBase {

  /**
   * The _title_callback for the entity.commerce_store.edit_form route
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $commerce_store
   *   The current store.
   *
   * @return string
   *   The page title
   */
  public function editPageTitle(StoreInterface $commerce_store) {
    return $this->t('Editing @label', ['@label' => $commerce_store->label()]);
  }

  /**
   * The _title_callback for the entity.commerce_store.canonical route
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $commerce_store
   *   The current store.
   *
   * @return string
   *   The page title
   */
  public function viewStoreTitle(StoreInterface $commerce_store) {
    return \Drupal\Component\Utility\Xss::filter($commerce_store->label());
  }

}
