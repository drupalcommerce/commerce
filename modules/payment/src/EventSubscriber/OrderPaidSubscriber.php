<?php

namespace Drupal\commerce_payment\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPaidSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.order.paid' => 'onPaid',
    ];
    return $events;
  }

  /**
   * Places the order after it has been fully paid through an off-site gateway.
   *
   * Off-site payments can only be made at checkout.
   * If the gateway supports notifications, these two scenarios are possible:
   *
   * 1) The onNotify() method is called before the customer returns to the
   *    site. A payment is created, the order is now considered fully paid,
   *    causing the "payment" step to no longer be visible, sending the
   *    customer back to the first checkout step.
   * 2) The customer never returns to the site. The onNotify() method completed
   *    the payment, but the order is still unplaced and stuck in checkout.
   *
   * To avoid both problems, this subscriber ensures that the order is placed,
   * which also ensures that the customer is sent to the checkout complete
   * page once they (eventually) return.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onPaid(OrderEvent $event) {
    $order = $event->getOrder();
    if ($order->getState()->getId() != 'draft') {
      // The order has already been placed.
      return;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->entity;
    if (!$payment_gateway) {
      // The payment gateway is unknown.
      return;
    }

    if ($payment_gateway->getPlugin() instanceof OffsitePaymentGatewayInterface) {
      $order->getState()->applyTransitionById('place');
      // A placed order should never be locked.
      $order->unlock();
    }
  }

}
