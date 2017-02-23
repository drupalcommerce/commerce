<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Applies promotions to orders during the order refresh process.
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
  protected $promotionCouponStorage;

  /**
   * Constructs a new PromotionOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->promotionCouponStorage = $entity_type_manager->getStorage('commerce_promotion_coupon');
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
      if (!$promotion) {
        $order->get('coupons')->removeItem($index);
        continue;
      }

      $context = new Context(new ContextDefinition('entity'), $coupon);

      /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface $plugin */
      $plugin = $promotion->get('offer')->first();
      $target_entity_type = $plugin->getTargetInstance()->getPluginDefinition()['target_entity_type'];
      if ($target_entity_type == 'commerce_order') {
        if ($promotion->applies($order)) {
          $promotion->apply($order, $context);
        }
      }
      elseif ($target_entity_type == 'commerce_order_item') {
        foreach ($order->getItems() as $order_item) {
          if ($promotion->applies($order_item)) {
            $promotion->apply($order_item, $context);
          }
        }
      }
    }
  }

}
