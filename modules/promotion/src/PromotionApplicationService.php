<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @todo Docblock
 * @todo Bikeshed name
 */
class PromotionApplicationService implements PromotionApplicationServiceInterface {

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * Constructs a new PromotionApplicationService object.
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
  public function apply(OrderInterface $order) {
    // Load the valid promotions for the order type and store.
    $promotions = $this->promotionStorage->loadValid($order->bundle(), $order->getStore());

    foreach ($order->getLineItems() as $line_item) {
      $this->applyToEntity($line_item, $promotions);
    }

    // Recalculate the order's total, after line item promotions applied.
    $order->recalculateTotalPrice();
    $this->applyToEntity($order, $promotions);
    // @todo only save if a promotion was applied.
    $order->save();
  }

  /**
   * Helper to apply promotions to an entity.
   *
   * @param \Drupal\commerce_order\EntityAdjustableInterface $entity
   *   The adjuatable entity.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface[] $promotions
   *   The array of possible promotions.
   */
  protected function applyToEntity(EntityAdjustableInterface $entity, array $promotions) {
    foreach ($promotions as $promotion) {
      try {
        // @todo When conditions land: if ($promotion->applies($entity) {
        $promotion->apply($entity);
        // @todo When conditions land: }
      }
      catch (ContextException $e) {
        // @todo We need the promotion to check if the entity applies to offer.
        continue;
      }
    }
    // @todo only save when promotion applied.
    $entity->save();
  }

}
