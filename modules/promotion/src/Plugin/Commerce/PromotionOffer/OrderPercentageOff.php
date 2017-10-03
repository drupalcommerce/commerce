<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the percentage off offer for orders.
 *
 * @CommercePromotionOffer(
 *   id = "order_percentage_off",
 *   label = @Translation("Percentage off the order total"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $adjustment_amount = $order->getTotalPrice()->multiply($this->getPercentage());
    $adjustment_amount = $this->rounder->round($adjustment_amount);

    $order->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $adjustment_amount->multiply('-1'),
      'percentage' => $this->getPercentage(),
      'source_id' => $promotion->id(),
    ]));
  }

}
