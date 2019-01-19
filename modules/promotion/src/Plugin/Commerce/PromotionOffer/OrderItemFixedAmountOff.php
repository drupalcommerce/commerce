<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the percentage off offer for order items.
 *
 * @CommercePromotionOffer(
 *   id = "order_item_fixed_amount_off",
 *   label = @Translation("Fixed amount off each matching product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemFixedAmountOff extends OrderItemPromotionOfferBase {

  use FixedAmountOffTrait;

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    $total_price = $order_item->getTotalPrice();
    $amount = $this->getAmount();
    if ($total_price->getCurrencyCode() != $amount->getCurrencyCode()) {
      return;
    }
    if ($this->configuration['display_inclusive']) {
      // Display-inclusive promotions must first be applied to the unit price.
      $unit_price = $order_item->getUnitPrice();
      if ($amount->greaterThan($unit_price)) {
        // Don't reduce the unit price past zero.
        $amount = $unit_price;
      }
      $new_unit_price = $unit_price->subtract($amount);
      $order_item->setUnitPrice($new_unit_price);
      $adjustment_amount = $amount->multiply($order_item->getQuantity());
      $adjustment_amount = $this->rounder->round($adjustment_amount);
    }
    else {
      $adjustment_amount = $amount->multiply($order_item->getQuantity());
      $adjustment_amount = $this->rounder->round($adjustment_amount);
      if ($adjustment_amount->greaterThan($total_price)) {
        // Don't reduce the order item total price past zero.
        $adjustment_amount = $total_price;
      }
    }

    $order_item->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $adjustment_amount->multiply('-1'),
      'source_id' => $promotion->id(),
      'included' => $this->configuration['display_inclusive'],
    ]));
  }

}
