<?php

namespace Drupal\commerce_store;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Defines the store storage.
 */
class StoreStorage extends CommerceContentEntityStorage implements StoreStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadDefault() {
    $query = $this->getQuery();
    $query
      ->sort('is_default', 'DESC')
      ->sort('store_id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE);
    $result = $query->execute();

    return $result ? $this->load(reset($result)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function markAsDefault(StoreInterface $store) {
    $store->setDefault(TRUE);
    $store->save();
  }

}
