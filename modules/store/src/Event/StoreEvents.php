<?php

namespace Drupal\commerce_store\Event;

final class StoreEvents {

  /**
   * Name of the event fired after loading a store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_LOAD = 'commerce_store.commerce_store.load';

  /**
   * Name of the event fired after creating a new store.
   *
   * Fired before the store is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_CREATE = 'commerce_store.commerce_store.create';

  /**
   * Name of the event fired before saving a store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_PRESAVE = 'commerce_store.commerce_store.presave';

  /**
   * Name of the event fired after saving a new store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_INSERT = 'commerce_store.commerce_store.insert';

  /**
   * Name of the event fired after saving an existing store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_UPDATE = 'commerce_store.commerce_store.update';

  /**
   * Name of the event fired before deleting a store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_PREDELETE = 'commerce_store.commerce_store.predelete';

  /**
   * Name of the event fired after deleting a store.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_DELETE = 'commerce_store.commerce_store.delete';

  /**
   * Name of the event fired after saving a new store translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_TRANSLATION_INSERT = 'commerce_store.commerce_store.translation_insert';

  /**
   * Name of the event fired after deleting a store translation.
   *
   * @Event
   *
   * @see \Drupal\commerce_store\Event\StoreEvent
   */
  const STORE_TRANSLATION_DELETE = 'commerce_store.commerce_store.translation_delete';

}
