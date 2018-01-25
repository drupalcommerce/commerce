<?php

namespace Drupal\commerce_payment\Event;

final class PaymentEvents {

  /**
   * Name of the event fired when payment gateways are loaded for an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent
   */
  const FILTER_PAYMENT_GATEWAYS = 'commerce_payment.filter_payment_gateways';

  /**
   * Name of the event fired after paying an order in full.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_ORDER_PAID_IN_FULL = 'commerce_payment.order_paid_in_full';

}
