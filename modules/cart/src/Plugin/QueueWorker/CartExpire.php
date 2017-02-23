<?php

namespace Drupal\commerce_cart\Plugin\QueueWorker;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Removes an expired Cart.
 *
 * @QueueWorker(
 *  id = "cart_expiries",
 *  title = @Translation("Commerce Cart expiration"),
 *  cron = {}
 * )
 */
class CartExpire extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $orders = \Drupal::entityTypeManager()->getStorage('commerce_order')
      ->loadMultiple($data);
    foreach ($orders as $order) {
      // Ensure that orders slated for clearing have not been completed since
      // they were last queued.
      if ($order instanceof OrderInterface && is_null($order->getCompletedTime())) {
        $order_type_storage = \Drupal::entityTypeManager()
          ->getStorage('commerce_order_type');
        $order_type = $order_type_storage->load($order->bundle());
        $elapsed = REQUEST_TIME - $order->getCreatedTime();
        $expiry = $order_type->getThirdPartySetting('commerce_cart',
            'cart_expiry') * 3600 * 24;
        if ($elapsed >= $expiry) {
          $order->delete();
        }
      }
    }
  }
}