<?php

namespace Drupal\commerce_cart\Plugin\QueueWorker;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Removes an expired Cart
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
    public function processItem($data)
    {
        if ($data instanceof OrderInterface && $data->getCompletedTime() > 0) {
            $order_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_type');
            $order_type = $order_type_storage->load($data->bundle());
            $elapsed = REQUEST_TIME - $data->getCreatedTime();
            $expiry = $order_type->getThirdPartySetting('commerce_cart', 'cart_expiry') * 3600 * 24;
            if ($elapsed >= $expiry) {
                $data->delete();
            }
        }
    }
}