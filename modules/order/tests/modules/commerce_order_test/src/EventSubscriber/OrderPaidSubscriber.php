<?php

namespace Drupal\commerce_order_test\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPaidSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PAID => 'onPaid',
    ];
  }

  /**
   * Increments an order flag each time the paid event gets dispatched.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onPaid(OrderEvent $event) {
    $order = $event->getOrder();
    $flag = $order->getData('order_test_called', 0);
    $flag++;
    $order->setData('order_test_called', $flag);
  }

}
