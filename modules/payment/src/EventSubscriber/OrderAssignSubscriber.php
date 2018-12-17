<?php

namespace Drupal\commerce_payment\EventSubscriber;

use Drupal\commerce_order\Event\OrderAssignEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderAssignSubscriber implements EventSubscriberInterface {

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

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    if ($payment_method && empty($payment_method->getOwnerId())) {
      $payment_method->setOwner($event->getAccount());
      $payment_method->save();
    }
  }

}
