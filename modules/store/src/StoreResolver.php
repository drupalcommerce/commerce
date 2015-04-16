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
  
  /**
   * Resolve() method to fire the event into the ResolveEvent class
   */
  protected function resolve(EventDispatcherInterface $eventDispatcher) {
    $event = new ResolveEvent($eventDispatcher);
    $this->eventDispatcher->dispatch(StoreEvents::RESOLVE, $event);
  }
  
}