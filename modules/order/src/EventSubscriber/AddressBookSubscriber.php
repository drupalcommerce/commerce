<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\commerce_order\AddressBookInterface;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Copies the order's profiles to the customer's address book.
 */
class AddressBookSubscriber implements EventSubscriberInterface {

  /**
   * The address book.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * Constructs a new AddressBookSubscriber object.
   *
   * @param \Drupal\commerce_order\AddressBookInterface $address_book
   *   The address book.
   */
  public function __construct(AddressBookInterface $address_book) {
    $this->addressBook = $address_book;
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
   * Copies the order's profiles when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $customer = $order->getCustomer();
    $profiles = $order->collectProfiles();
    foreach ($profiles as $profile) {
      if ($this->addressBook->needsCopy($profile)) {
        $this->addressBook->copy($profile, $customer);
      }
    }
  }

  /**
   * Copies the order's profiles when the order is assigned.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event.
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    $customer = $event->getCustomer();
    $profiles = $order->collectProfiles();
    foreach ($profiles as $profile) {
      if ($this->addressBook->needsCopy($profile)) {
        $this->addressBook->copy($profile, $customer);
      }
    }
  }

}
