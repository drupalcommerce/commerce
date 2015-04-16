<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreResolver.
 */

namespace Drupal\commerce_store;

use Drupal\commerce_store\ResolveEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StoreResolver implements StoreEvents {
  
  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  
  /**
   * Constructs the resolver.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }
  
  // needs resolve() method which will fire the RESOLVE event and fire it into the ResolveEvent class
  
}