<?php

namespace Drupal\commerce_promotion\EventSubscriber;

use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;

class OrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * The coupon storage.
   *
   * @var \Drupal\commerce_promotion\CouponStorageInterface
   */
  protected $couponStorage;

  /**
   * The promotion usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

  /**
   * Constructs a new OrderEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The promotion usage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PromotionUsageInterface $usage) {
    $this->promotionStorage = $entity_type_manager->getStorage('commerce_promotion');
    $this->couponStorage = $entity_type_manager->getStorage(('commerce_promotion_coupon'));
    $this->usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => 'registerUsage',
    ];
    return $events;
  }

  /**
   * Registers promotion usage when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function registerUsage(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $coupon_promotion_ids = [];
    foreach ($order->coupons->referencedEntities() as $coupon) {
      /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
      $this->usage->register($order, $coupon->getPromotion(), $coupon);
      $coupon_promotion_ids[] = $coupon->getPromotionId();
    }

    $adjustments = $order->collectAdjustments();
    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() != 'promotion') {
        continue;
      }

      $promotion_id = $adjustment->getSourceId();
      if ($promotion_id && !in_array($promotion_id, $coupon_promotion_ids)) {
        $promotion = $this->promotionStorage->load($promotion_id);
        $this->usage->register($order, $promotion);
      }
    }
  }

}
