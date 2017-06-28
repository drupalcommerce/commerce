<?php

namespace Drupal\commerce_payment\Event;

final class PaymentEvents {

  /**
   * Name of the event fired when payment gateways loaded for an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\FilterOrderGatewaysEvent
   */
  const FILTER_ORDER_GATEWAYS = 'commerce_payment.filter_order_gateways';

}
