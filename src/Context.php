<?php

namespace Drupal\commerce;

use Drupal\Core\Datetime\DateTime;
use Drupal\commerce_store\Entity\Store;
use Drupal\profile\Entity\Profile;

final class Context {

  /**
   * The customer profile.
   *
   * @var \Drupal\profile\Entity\Profile
   */
  protected $customer;

  /**
   * The stores.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * The time.
   *
   * @var \Drupal\Core\Datetime\DateTime
   */
  protected $date;

  /**
   * Constructs a new Commerce Context object.
   *
   * @param \Drupal\profile\Entity\Profile $customer
   *   The customer profile.
   *
   * @param \Drupal\commerce_store\Entity\Store $store
   *   The store.
   *
   * @param \Drupal\Core\Datetime\DateTime $date
   *   The date and time.
   */
  public function __construct(Profile $customer, Store $store, DateTime $date) {
    $this->customer = $customer;
    $this->store = $store;
    $this->date = $date;
  }

  /**
   * Gets the current customer.
   *
   * @return \Drupal\profile\Entity\Profile $customer
   */
  public function getCustomer() {
    return $this->customer;
  }

  /**
   * Gets the current store.
   *
   * @return \Drupal\commerce_store\Entity\Store
   */
  public function getStore() {
    return $this->store;
  }

  /**
   * Gets the current date.
   *
   * @return \Drupal\Core\Datetime\DateTime
   */
  public function getDate() {
    return $this->date;
  }
}

