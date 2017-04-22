<?php

namespace Drupal\commerce_payment\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
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
   * The payment operation amount.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount;

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
   * @param \Drupal\commerce_price\Price $amount
   *   The payment operation amount.
   */
  public function setAmount(Price $amount) {
    $this->amount = $amount;
  }

  /**
   * Gets the payment operation amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The payment operation amount.
   */
  public function getAmount() {
    return $this->amount;
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
