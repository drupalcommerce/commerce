<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
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
class OrderItemPercentageOff extends OrderItemPromotionOfferBase {

  use PercentageOffTrait;

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    $percentage = $this->getPercentage();
    if ($this->configuration['display_inclusive']) {
      // Display-inclusive promotions must first be applied to the unit price.
      $unit_price = $order_item->getUnitPrice();
      $amount = $unit_price->multiply($percentage);
      $amount = $this->rounder->round($amount);
      $new_unit_price = $unit_price->subtract($amount);
      $order_item->setUnitPrice($new_unit_price);
      $adjustment_amount = $amount->multiply($order_item->getQuantity());
    }
    else {
      $adjustment_amount = $order_item->getTotalPrice()->multiply($percentage);
    }
    $adjustment_amount = $this->rounder->round($adjustment_amount);

    $order_item->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $adjustment_amount->multiply('-1'),
      'percentage' => $percentage,
      'source_id' => $promotion->id(),
      'included' => $this->configuration['display_inclusive'],
    ]));
  }

}
