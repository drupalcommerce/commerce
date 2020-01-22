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
    // Remove coupons that are no longer valid (due to availability/conditions.)
    $coupons_field_list = $order->get('coupons');
    $constraints = $coupons_field_list->validate();
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $constraint */
    foreach ($constraints as $constraint) {
      list($delta, $property_name) = explode('.', $constraint->getPropertyPath());
      $coupons_field_list->removeItem($delta);
    }

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $index => $coupon) {
      $promotion = $coupon->getPromotion();
      $promotion->apply($order);
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
