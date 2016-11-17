<?php

namespace Drupal\commerce;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;

final class Context {

  /**
   * The user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

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
   * Constructs a new Commerce Context object.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param int|null $time
   *   The unix timestamp, or NULL to use the current request time.
   */
  public function __construct(AccountInterface $user, StoreInterface $store, $time = NULL) {
    $this->user = $user;
    $this->store = $store;
    $this->time = $time ?: time();
  }

  /**
   * Gets the user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user.
   */
  public function getUser() {
    return $this->user;
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
