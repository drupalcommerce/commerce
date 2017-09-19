<?php

namespace Drupal\commerce_payment\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the payment event.
 *
 * @see \Drupal\commerce_payment\Event\PaymentEvents
 */
class PaymentEvent extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Constructs a new PaymentEvent.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   */
  public function __construct(PaymentInterface $payment) {
    $this->payment = $payment;
  }

  /**
   * Gets the payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment.
   */
  public function getPayment() {
    return $this->payment;
  }

}
