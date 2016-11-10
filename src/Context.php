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
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   The user entity or NULL if anonymous.
   * @param \Drupal\commerce_store\Entity\StoreInterface|null $store
   *   The store entity or NULL.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   */
  public function __construct($user, $store, DrupalDateTime $date) {
    $this->user = $user;
    $this->store = $store;
    $this->date = $date;
  }

  /**
   * Gets the user entity.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The user entity or NULL if anonymous.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Gets the store entity.
   *
   * @return \Drupal\commerce_store\Entity\Store
   *   The store entity or NULL.
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
