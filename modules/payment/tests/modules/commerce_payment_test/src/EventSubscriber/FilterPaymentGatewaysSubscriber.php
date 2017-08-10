<?php

namespace Drupal\commerce_payment_test\EventSubscriber;

use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterPaymentGatewaysSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PaymentEvents::FILTER_PAYMENT_GATEWAYS => 'onFilter',
    ];
  }

  /**
   * Filters out payment gateways listed in an order's data attribute.
   *
   * @param \Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent $event
   *   The event.
   */
  public function onFilter(FilterPaymentGatewaysEvent $event) {
    $payment_gateways = $event->getPaymentGateways();
    $excluded_gateways = $event->getOrder()->getData('excluded_gateways', []);
    foreach ($payment_gateways as $payment_gateway_id => $payment_gateway) {
      if (in_array($payment_gateway->id(), $excluded_gateways)) {
        unset($payment_gateways[$payment_gateway_id]);
      }
    }
    $event->setPaymentGateways($payment_gateways);
  }

}
