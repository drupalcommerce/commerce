<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies promotions to orders during the order refresh process.
 *
 * @see \Drupal\commerce_promotion\CouponOrderProcessor
 */
class PromotionOrderProcessor implements OrderProcessorInterface {

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * Constructs a new PromotionOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $index => $coupon) {
      $promotion = $coupon->getPromotion();
      if ($coupon->available($order) && $promotion->applies($order)) {
        $promotion->apply($order);
      }
      else {
        // The promotion is no longer available (end date, usage, etc).
        $order->get('coupons')->removeItem($index);
      }
    }

    // Non-coupon promotions are loaded and applied separately.
    $promotions = $this->promotionStorage->loadAvailable($order);
    foreach ($promotions as $promotion) {
      if ($promotion->applies($order)) {
        $promotion->apply($order);
      }
    }
  }

}
