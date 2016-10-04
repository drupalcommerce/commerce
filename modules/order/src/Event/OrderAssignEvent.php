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
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Constructs a new OrderAssignEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  public function __construct(OrderInterface $order, UserInterface $account) {
    $this->order = $order;
    $this->account = $account;
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
   * Gets the user account.
   *
   * @return \Drupal\user\UserInterface
   *   The user account.
   */
  public function getAccount() {
    return $this->account;
  }

}
