<?php

namespace Drupal\commerce_payment\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Deletes payment methods in groups of 50.
 *
 * @QueueWorker(
 *   id = "payment_methods_delete_queue",
 *   title = @Translation("Queue for deletion of payment methods"),
 *   cron = {"time" = 60}
 * )
 */
class PaymentMethodDeleteQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
    $payment_method_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_payment_method');
    $payment_method_storage->delete($data);
  }

}
