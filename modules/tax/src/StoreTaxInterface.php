<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\StoreInterface;

interface StoreTaxInterface {

  /**
   * Gets the default tax type for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_tax\Entity\TaxTypeInterface|null
   *   The default tax type, or NULL if none apply.
   */
  public function getDefaultTaxType(StoreInterface $store);

  /**
   * Gets the default tax zones for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\commerce_tax\TaxZone[]
   *   The tax zones.
   */
  public function getDefaultZones(StoreInterface $store);

  /**
   * Gets the default tax rates for the given store and order item.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return \Drupal\commerce_tax\TaxRate[]
   *   The tax rates, keyed by tax zone ID.
   */
  public function getDefaultRates(StoreInterface $store, OrderItemInterface $order_item);

  /**
   * Clears the static caches.
   */
  public function clearCaches();

}
