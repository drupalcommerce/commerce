<?php

namespace Drupal\commerce_payment\EventSubscriber;

use Drupal\commerce_order\AddressBookInterface;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderAssignSubscriber implements EventSubscriberInterface {

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
    $events = [
      'commerce_order.order.assign' => 'onAssign',
    ];
    return $events;
  }

  /**
   * Assigns anonymous payment methods to the new customer.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event.
   */
  public function onAssign(OrderAssignEvent $event) {
    $order = $event->getOrder();
    if ($order->get('payment_method')->isEmpty()) {
      return;
    }

    $customer = $event->getCustomer();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    if ($payment_method && empty($payment_method->getOwnerId())) {
      $payment_method_profile = $payment_method->getBillingProfile();
      if ($payment_method_profile && $this->addressBook->needsCopy($payment_method_profile)) {
        $this->addressBook->copy($payment_method_profile, $customer);
        // Transfer the address book profile ID to the order billing profile.
        $billing_profile = $order->getBillingProfile();
        if ($payment_method_profile->equalToProfile($billing_profile)) {
          $address_book_profile_id = $payment_method_profile->getData('address_book_profile_id');
          $billing_profile->setData('address_book_profile_id', $address_book_profile_id);
          $billing_profile->save();
        }
      }
      $payment_method->setOwner($customer);
      $payment_method->save();
    }
  }

}
