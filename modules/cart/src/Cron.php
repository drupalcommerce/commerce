<?php

namespace Drupal\commerce_cart;

use Drupal\commerce\Interval;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Default cron implementation.
 *
 * Queues abandoned carts for expiration (deletion).
 *
 * @see \Drupal\commerce_cart\Plugin\QueueWorker\CartExpiration
 */
class Cron implements CronInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The commerce_cart_expiration queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a new Cron object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->queue = $queue_factory->get('commerce_cart_expiration');
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types */
    $order_types = $this->orderTypeStorage->loadMultiple();
    foreach ($order_types as $order_type) {
      $cart_expiration = $order_type->getThirdPartySetting('commerce_cart', 'cart_expiration');
      if (empty($cart_expiration)) {
        continue;
      }

      $interval = new Interval($cart_expiration['number'], $cart_expiration['unit']);
      $all_order_ids = $this->getOrderIds($order_type->id(), $interval);
      foreach (array_chunk($all_order_ids, 50) as $order_ids) {
        $this->queue->createItem($order_ids);
      }
    }
  }

  /**
   * Gets the applicable order IDs.
   *
   * @param string $order_type_id
   *   The order type ID.
   * @param \Drupal\commerce\Interval $interval
   *   The expiration interval.
   *
   * @return array
   *   The order IDs.
   */
  protected function getOrderIds($order_type_id, Interval $interval) {
    $current_date = new DrupalDateTime('now');
    $expiration_date = $interval->subtract($current_date);
    $ids = $this->orderStorage->getQuery()
      ->condition('type', $order_type_id)
      ->condition('changed', $expiration_date->getTimestamp(), '<=')
      ->condition('cart', TRUE)
      ->range(0, 250)
      ->accessCheck(FALSE)
      ->addTag('commerce_cart_expiration')
      ->execute();

    return $ids;
  }

}
