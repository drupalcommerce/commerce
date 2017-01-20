<?php

namespace Drupal\commerce_payment\Event;

final class PaymentEvents {

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

  /**
   * Name of the event fired after authorizing a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_AUTHORIZED = 'commerce_payment.payment.authorized';

  /**
   * Name of the event fired after voiding a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_VOIDED = 'commerce_payment.payment.voided';

  /**
   * Name of the event fired after expiring a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_EXPIRED = 'commerce_payment.payment.expired';

  /**
   * Name of the event fired after authorizing and capturing a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_AUTHORIZED_CAPTURED = 'commerce_payment.payment.authorized_captured';

  /**
   * Name of the event fired after partially capturing a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_PARTIALLY_CAPTURED = 'commerce_payment.payment.partially_captured';

  /**
   * Name of the event fired after fully capturing a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_CAPTURED = 'commerce_payment.payment.captured';

  /**
   * Name of the event fired after partially refunding a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_PARTIALLY_REFUNDED = 'commerce_payment.payment.partially_refunded';

  /**
   * Name of the event fired after fully refunding a payment.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment\Event\PaymentEvent
   */
  const PAYMENT_REFUNDED = 'commerce_payment.payment.refunded';

}
