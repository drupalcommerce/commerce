<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the percentage off offer for order items.
 *
 * @CommercePromotionOffer(
 *   id = "order_item_percentage_off",
 *   label = @Translation("Percentage off each matching product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemPercentageOff extends PercentageOffBase {

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;

    $item_adjustments = $order_item->getAdjustments();
    $item_adjustment_total = 0;

    // Previous adjustments on the order item need to be included in
    // calculations.
    if (count($item_adjustments)) {
      foreach ($item_adjustments as $item_adjustment) {
        $item_adjustment_total += (float) $item_adjustment->getAmount()->getNumber();
      }
    }

    $item_adjustment_amount = new Price((string) $item_adjustment_total, $item_adjustment->getAmount()->getCurrencyCode());
    $adjustment_amount = $order_item->getUnitPrice()->add($item_adjustment_amount)->multiply($this->getPercentage());
    $adjustment_amount = $this->rounder->round($adjustment_amount);

    if (!$adjustment_amount->isZero()) {
      $order_item->addAdjustment(new Adjustment([
        'type' => 'promotion',
        // @todo Change to label from UI when added in #2770731.
        'label' => t('Discount'),
        'amount' => $adjustment_amount->multiply('-1'),
        'percentage' => $this->getPercentage(),
        'source_id' => $promotion->id(),
      ]));
    }
  }

}
