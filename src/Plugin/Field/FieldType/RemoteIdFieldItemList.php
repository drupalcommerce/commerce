<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines the 'commerce_remote_id' field item list class.
 */
class RemoteIdFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getByProvider($provider) {
    foreach ($this->list as $delta => $item) {
      if ($item->provider == $provider) {
        return $item->remote_id;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setByProvider($provider, $remote_id) {
    $target_item = NULL;
    foreach ($this->list as $delta => $item) {
      if ($item->provider == $provider) {
        $target_item = $item;
        break;
      }
    }
    $target_item = $target_item ?: $this->appendItem();
    $target_item->provider = $provider;
    $target_item->remote_id = $remote_id;
  }

}
