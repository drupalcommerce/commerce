<?php

namespace Drupal\commerce;

use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerTrait as CoreEntityOwnerTrait;

/**
 * Provides a trait for Commerce entities that have an owner.
 */
trait EntityOwnerTrait {

  use CoreEntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $key = $this->getEntityType()->getKey('owner');
    $owner = $this->get($key)->entity;
    // Handle deleted customers.
    if (!$owner) {
      $owner = User::getAnonymousUser();
    }
    return $owner;
  }

}
