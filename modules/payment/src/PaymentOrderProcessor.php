<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Recalculates the order's total_paid field.
 */
class PaymentOrderProcessor implements OrderProcessorInterface {

  /**
   * The payment order updater.
   *
   * @var \Drupal\commerce_payment\PaymentOrderUpdaterInterface
   */
  protected $paymentOrderUpdater;

  /**
   * Constructs a new PaymentOrderProcessor instance.
   *
   * @param \Drupal\commerce_payment\PaymentOrderUpdaterInterface $payment_order_updater
   *   The order update manager.
   */
  public function __construct(PaymentOrderUpdaterInterface $payment_order_updater) {
    $this->paymentOrderUpdater = $payment_order_updater;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($this->paymentOrderUpdater->needsUpdate($order)) {
      $this->paymentOrderUpdater->updateOrder($order);
    }
  }

}
