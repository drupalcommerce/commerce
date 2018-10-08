<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PaymentOrderManager implements PaymentOrderManagerInterface {

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * Constructs a new PaymentOrderManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
  }

  /**
   * {@inheritdoc}
   */
  public function updateTotalPaid(OrderInterface $order) {
    $previous_total = $order->getTotalPaid();
    if (!$previous_total) {
      // A NULL total indicates an order that doesn't have any items yet.
      return;
    }
    // The new total is always calculated from scratch, to properly handle
    // orders that were created before the total_paid field was introduced.
    $payments = $this->paymentStorage->loadMultipleByOrder($order);
    /** @var \Drupal\commerce_price\Price $new_total */
    $new_total = new Price('0', $previous_total->getCurrencyCode());
    foreach ($payments as $payment) {
      if ($payment->isCompleted()) {
        $new_total = $new_total->add($payment->getBalance());
      }
    }

    if (!$previous_total->equals($new_total)) {
      $order->setTotalPaid($new_total);
      $order->save();
    }
  }

}
