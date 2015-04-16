<?php

/**
 * @file
 * Contains \Drupal\commerce_store\ResolveEvent.
 */

namespace Drupal\commerce_store;

use Symfony\Component\EventDispatcher\Event;
use Drupal\commerce_store\Entity\Store;

/**
 * Event fired when rendering store.
 *
 * @see \Drupal\commerce_store\StoreEvents::RESOLVE
 */
class ResolveEvent extends Event {
  
  /**
   * The selected store.
   *
   */
  protected $store;
  
  /**
   * Constructs the resolve event.
   *
   * @param store
   */
  public function __construct(Entity $store) {
    $this->store = $store;
  }
  
  /**
   * Getter for the store object.
   *
   * @return Store
   */
  public function getStore() {
    return $this->store;
  }
 
  /**
   * Setter for the store object.
   *
   * @param $store
   */
  public function setStore($store) {
    $this->store = $store;
  }
  
}
