<?php

namespace Drupal\commerce;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Contains known global information (customer, store, time).
 *
 * Passed to price resolvers and availability checkers.
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
   * The data.
   *
   * Used to provide additional information for a specific set of consumers
   * (e.g. price resolvers).
   *
   * @var array
   */
  protected $data;

  /**
   * Constructs a new Context object.
   *
   * @param \Drupal\Core\Session\AccountInterface $customer
   *   The customer.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param int|null $time
   *   The unix timestamp, or NULL to use the current time.
   * @param array $data
   *   The data.
   */
  public function __construct(AccountInterface $customer, StoreInterface $store, int $time = NULL, array $data = []) {
    $this->customer = $customer;
    $this->store = $store;
    $this->time = $time ?: time();
    $this->data = $data;
  }

  /**
   * Gets the customer.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The customer.
   */
  public function getCustomer() : AccountInterface {
    return $this->customer;
  }

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store.
   */
  public function getStore() : StoreInterface {
    return $this->store;
  }

  /**
   * Gets the time.
   *
   * @return int
   *   The time.
   */
  public function getTime() : int {
    return $this->time;
  }

  /**
   * Gets a data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData(string $key, $default = NULL) {
    return isset($this->data[$key]) ? $this->data[$key] : $default;
  }

}
