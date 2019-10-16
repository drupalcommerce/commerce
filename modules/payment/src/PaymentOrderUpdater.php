<?php

namespace Drupal\commerce_payment;

use Drupal\Core\DestructableInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PaymentOrderUpdater implements PaymentOrderUpdaterInterface, DestructableInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The order IDs that need updating.
   *
   * @var int[]
   */
  protected $updateList = [];

  /**
   * Constructs a new PaymentOrderUpdater object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function requestUpdate(OrderInterface $order) {
    $this->updateList[$order->id()] = $order->id();
  }

  /**
   * {@inheritdoc}
   */
  public function needsUpdate(OrderInterface $order) {
    return !$order->isNew() && isset($this->updateList[$order->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrders() {
    if (!empty($this->updateList)) {
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
      $orders = $order_storage->loadMultiple($this->updateList);
      foreach ($orders as $order) {
        $this->updateOrder($order, TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrder(OrderInterface $order, $save_order = FALSE) {
    $previous_total = $order->getTotalPaid();
    if (!$previous_total) {
      // A NULL total indicates an order that doesn't have any items yet.
      return;
    }
    // The new total is always calculated from scratch, to properly handle
    // orders that were created before the total_paid field was introduced.
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    /** @var \Drupal\commerce_price\Price $new_total */
    $new_total = new Price('0', $previous_total->getCurrencyCode());
    foreach ($payments as $payment) {
      if ($payment->isCompleted()) {
        $new_total = $new_total->add($payment->getBalance());
      }
    }

    if (!$previous_total->equals($new_total)) {
      $order->setTotalPaid($new_total);
      if ($save_order) {
        $order->save();
      }
    }

    unset($this->updateList[$order->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    $this->updateOrders();
  }

}
