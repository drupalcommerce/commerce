<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreEvents.
 */

namespace Drupal\commerce_store;

/**
 * Defines events for the store system.
 */
final class StoreEvents {

  /**
   * @Event
   *
   * @see \Drupal\commerce_store\ResolveEvent
   */
  const RESOLVE = 'commerce.store.resolve';
  
}
