<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the interface for the payment storage.
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
  public function loadForOrder(OrderInterface $order);

}
