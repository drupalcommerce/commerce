<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for payment storage.
 */
interface PaymentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads all payments for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
   *   The payments.
   */
  public function loadMultipleForOrder(OrderInterface $order);

}
