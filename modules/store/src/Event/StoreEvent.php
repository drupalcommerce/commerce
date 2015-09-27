<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Event\StoreEvent.
 */

namespace Drupal\commerce_store\Event;

use Drupal\commerce_store\Entity\StoreInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the store event.
 *
 * @see \Drupal\commerce_store\Event\StoreEvents
 */
class StoreEvent extends Event {

  /**
   * The store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * Constructs a new StoreEvent.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   */
  public function __construct(StoreInterface $store) {
    $this->store = $store;
  }

  /**
   * The store the event refers to.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   */
  public function getStore() {
    return $this->store;
  }

}
