<?php

namespace Drupal\commerce_payment_test\EventSubscriber;

use Drupal\commerce_payment\Event\FilterOrderGatewaysEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterOrderGatewaysSubscriber implements EventSubscriberInterface {

  /**
   * Filters out gateways listed in an order's data attribute.
   *
   * @param \Drupal\commerce_payment\Event\FilterOrderGatewaysEvent $event
   *   The event.
   */
  public function filter(FilterOrderGatewaysEvent $event) {
    $order = $event->getOrder();
    $gateways = &$event->getGateways();
    $filtered_gateways = $order->getData('test_filtered_gateways', []);
    foreach ($gateways as $key => $gateway) {
      if (in_array($gateway->id(), $filtered_gateways)) {
        unset($gateways[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PaymentEvents::FILTER_ORDER_GATEWAYS => 'filter',
    ];
  }

}
