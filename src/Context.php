<?php

namespace Drupal\commerce;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Contains known global information (customer, store, time).
 *
 * Passed to price resolvers, order processors, availability checkers.
 */
final class Context {

  /**
   * The customer.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $customer;

  /**
   * The store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * The time.
   *
   * @var int
   */
  protected $time;

  /**
   * Constructs a new Context object.
   *
   * @param \Drupal\Core\Session\AccountInterface $customer
   *   The customer.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param int|null $time
   *   The unix timestamp, or NULL to use the current time.
   */
  public function __construct(AccountInterface $customer, StoreInterface $store, $time = NULL) {
    $this->customer = $customer;
    $this->store = $store;
    $this->time = $time ?: time();
  }

  /**
   * Gets the customer.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The customer.
   */
  public function getCustomer() {
    return $this->customer;
  }

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\Store
   *   The store.
   */
  public function getStore() {
    return $this->store;
  }

  /**
   * Gets the time.
   *
   * @return int
   *   The time.
   */
  public function getTime() {
    return $this->time;
  }

}
