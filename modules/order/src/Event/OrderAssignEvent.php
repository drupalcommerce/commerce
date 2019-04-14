<?php

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the order assign event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class OrderAssignEvent extends Event {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The customer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customer;

  /**
   * Constructs a new OrderAssignEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   */
  public function __construct(OrderInterface $order, UserInterface $customer) {
    $this->order = $order;
    $this->customer = $customer;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the customer.
   *
   * @return \Drupal\user\UserInterface
   *   The customer.
   */
  public function getCustomer() {
    return $this->customer;
  }

  /**
   * Gets the user account.
   *
   * @deprecated Use getCustomer() instead.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->getCustomer();
  }

}
