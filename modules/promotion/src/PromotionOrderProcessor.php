<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies promotions to orders during the order refresh process.
 */
class PromotionOrderProcessor implements OrderProcessorInterface {

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Constructs a new PromotionOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $order_type = $this->orderTypeStorage->load($order->bundle());
    $promotions = $this->promotionStorage->loadValid($order_type, $order->getStore());
    foreach ($promotions as $promotion) {
      if ($promotion->applies($order)) {
        $promotion->apply($order);
      }
    }
  }

}
