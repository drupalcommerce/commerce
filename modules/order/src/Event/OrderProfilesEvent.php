<?php

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order profiles event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class OrderProfilesEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The profiles, keyed by scope (billing, shipping, etc).
   *
   * @var \Drupal\profile\Entity\ProfileInterface[]
   */
  protected $profiles = [];

  /**
   * Constructs a new OrderProfilesEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\profile\Entity\ProfileInterface[] $profiles
   *   The profiles.
   */
  public function __construct(OrderInterface $order, array $profiles) {
    $this->order = $order;
    $this->profiles = $profiles;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   Gets the order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the profiles.
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *   The profiles.
   */
  public function getProfiles() {
    return $this->profiles;
  }

  /**
   * Sets the profiles.
   *
   * @param \Drupal\profile\Entity\ProfileInterface[] $profiles
   *   The profiles.
   *
   * @return $this
   */
  public function setProfiles(array $profiles) {
    $this->profiles = $profiles;
    return $this;
  }

  /**
   * Adds a profile for the given scope.
   *
   * @param string $scope
   *   The scope (billing, shipping, etc).
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return $this
   */
  public function addProfile($scope, ProfileInterface $profile) {
    $this->profiles[$scope] = $profile;
    return $this;
  }

  /**
   * Removes the profile for the given scope.
   *
   * @param string $scope
   *   The scope (billing, shipping, etc).
   *
   * @return $this
   */
  public function removeProfile($scope) {
    unset($this->profiles[$scope]);
    return $this;
  }

  /**
   * Gets whether a profile exists for the given scope.
   *
   * @param string $scope
   *   The scope (billing, shipping, etc).
   *
   * @return bool
   *   TRUE if the profile exists, FALSE otherwise.
   */
  public function hasProfile($scope) {
    return isset($this->profiles[$scope]);
  }

}
