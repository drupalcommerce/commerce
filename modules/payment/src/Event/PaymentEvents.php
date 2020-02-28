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
   * Name of the event fired after loading a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_LOAD = 'commerce_payment.commerce_payment.load';

  /**
   * Name of the event fired after creating a new payment.
   *
   * Fired before the payment is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_CREATE = 'commerce_payment.commerce_payment.create';

  /**
   * Name of the event fired before saving a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_PRESAVE = 'commerce_payment.commerce_payment.presave';

  /**
   * Name of the event fired after saving a new payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_INSERT = 'commerce_payment.commerce_payment.insert';

  /**
   * Name of the event fired after saving an existing payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_UPDATE = 'commerce_payment.commerce_payment.update';

  /**
   * Name of the event fired before deleting a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_PREDELETE = 'commerce_payment.commerce_payment.predelete';

  /**
   * Name of the event fired after deleting a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_DELETE = 'commerce_payment.commerce_payment.delete';

}
