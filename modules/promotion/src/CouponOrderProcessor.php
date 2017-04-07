<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Applies coupon promotions to orders during the order refresh process.
 *
 * @see \Drupal\commerce_promotion\PromotionOrderProcessor
 */
class CouponOrderProcessor implements OrderProcessorInterface {

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
   * The coupon storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $couponStorage;

  /**
   * Constructs a new CouponOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->couponStorage = $entity_type_manager->getStorage('commerce_promotion_coupon');
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if (!$order->hasField('coupons') || $order->get('coupons')->isEmpty()) {
      return;
    }

    $order_type = $this->orderTypeStorage->load($order->bundle());
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $index => $coupon) {
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
      $promotion = $this->promotionStorage->loadByCoupon($order_type, $order->getStore(), $coupon);

      // The promotion may have become invalid (inactive/expired), causing the
      // query in loadByCoupon() to filter it out.
      if (!$promotion) {
        $order->get('coupons')->removeItem($index);
        continue;
      }

      if ($promotion->applies($order)) {
        $promotion->apply($order);
      }
    }
  }

}
