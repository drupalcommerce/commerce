<?php

namespace Drupal\commerce_tax\Event;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the customer profile event.
 *
 * @see \Drupal\commerce_tax\Event\TaxEvents
 */
class CustomerProfileEvent extends Event {

  /**
   * The customer profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $customerProfile;

  /**
   * The order item.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface
   */
  protected $orderItem;

  /**
   * Constructs a new CustomerProfileEvent.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $customer_profile
   *   The initially selected customer profile.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   */
  public function __construct(ProfileInterface $customer_profile = NULL, OrderItemInterface $order_item) {
    $this->customerProfile = $customer_profile;
    $this->orderItem = $order_item;
  }

  /**
   * Gets the customer profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The customer profile, or NULL if not yet known.
   */
  public function getCustomerProfile() {
    return $this->customerProfile;
  }

  /**
   * Sets the customer profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $customer_profile
   *   The customer profile.
   *
   * @return $this
   */
  public function setCustomerProfile(ProfileInterface $customer_profile) {
    $this->customerProfile = $customer_profile;
    return $this;
  }

  /**
   * Gets the order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  public function getOrderItem() {
    return $this->orderItem;
  }

}
