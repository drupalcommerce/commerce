<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_promotion\Entity\Promotion;
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
    // Collect all promotions.
    $promotions = [
      Promotion::COMPATIBLE_ANY => [],
      Promotion::COMPATIBLE_NONE => [],
    ];
    $order_type = $this->orderTypeStorage->load($order->bundle());
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $index => $coupon) {
      if ($coupon->available($order)) {
        $promotion = $coupon->getPromotion();
        $promotions[$promotion->getCompatibility()][$promotion->id()] = $promotion;
      }
      else {
        // The promotion is no longer available (end date, usage, etc).
        $order->get('coupons')->removeItem($index);
      }
    }
    // Non-coupon promotions
    foreach ($this->promotionStorage->loadAvailable($order_type, $order->getStore()) as $promotion) {
      $promotions[$promotion->getCompatibility()][$promotion->id()] = $promotion;
    }

    // Apply promotions. COMPATIBLE_NONE should be last.
    foreach ($promotions as $compatibility => $collection) {
      foreach ($collection as $promotion) {
        if ($promotion->applies($order)) {
          $promotion->apply($order);
        }
      }
    }
  }

}
