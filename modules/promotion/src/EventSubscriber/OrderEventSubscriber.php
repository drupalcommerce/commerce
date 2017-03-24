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
      'commerce_order.place.pre_transition' => 'addUsage',
    ];
    return $events;
  }

  /**
   * Adds promotion usage when cart placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   *
   * @todo Investigate moving to kernel terminate somehow, for performance.
   */
  public function addUsage(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    /** @var \Drupal\commerce_order\Adjustment[] $adjustments */
    $adjustments = $order->collectAdjustments();

    foreach ($adjustments as $adjustment) {
      if ($adjustment->getType() == 'promotion') {
        $promotion = $this->promotionStorage->load($adjustment->getSourceId());
        $this->usage->addUsage($order, $promotion);
      }
      if ($adjustment->getType() == 'promotion_coupon') {
        /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
        $coupon = $this->couponStorage->load($adjustment->getSourceId());
        $this->usage->addUsage($order, $coupon->getPromotion(), $coupon);
      }
    }
  }

}
