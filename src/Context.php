<?php

namespace Drupal\commerce;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;

final class Context {

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * The date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $date;

  /**
   * Constructs a new Commerce Context object.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   */
  public function __construct(AccountInterface $user, StoreInterface $store, DrupalDateTime $date) {
    $this->user = $user;
    $this->store = $store;
    $this->date = $date;
  }

  /**
   * Gets the user entity.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user entity.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Gets the store entity.
   *
   * @return \Drupal\commerce_store\Entity\Store
   *   The store entity.
   */
  public function getStore() {
    return $this->store;
  }

  /**
   * Gets the date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The date object.
   */
  public function getDate() {
    return $this->date;
  }

}
