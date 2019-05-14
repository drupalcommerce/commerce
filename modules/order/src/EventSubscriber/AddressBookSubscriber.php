<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\commerce_order\AddressBookManagerInterface;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Copies the order's billing information to the customer's address book.
 */
class AddressBookSubscriber implements EventSubscriberInterface {

  /**
   * The address book manager.
   *
   * @var \Drupal\commerce_order\AddressBookManagerInterface
   */
  protected $addressBookManager;

  /**
   * Constructs a new AddressBookSubscriber object.
   *
   * @param \Drupal\commerce_order\AddressBookManagerInterface $address_book_manager
   *   The address book manager.
   */
  public function __construct(AddressBookManagerInterface $address_book_manager) {
    $this->addressBookManager = $address_book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['onOrderPlace', 100],
      'commerce_order.order.assign' => ['onOrderAssign', 100],
    ];
  }

  /**
   * Copies the order's billing information when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $profile = $order->getBillingProfile();
    $customer = $order->getCustomer();
    if ($profile && $this->addressBookManager->needsCopy($profile)) {
      $this->addressBookManager->copy($profile, $customer);
    }
  }

  /**
   * Copies the order's billing information when the order is assigned.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event.
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    $profile = $order->getBillingProfile();
    $customer = $event->getCustomer();
    if ($profile && $this->addressBookManager->needsCopy($profile)) {
      $this->addressBookManager->copy($profile, $customer);
    }
  }

}
