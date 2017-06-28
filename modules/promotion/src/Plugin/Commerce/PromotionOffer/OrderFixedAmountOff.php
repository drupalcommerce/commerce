<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the fixed amount off offer for orders.
 *
 * @CommercePromotionOffer(
 *   id = "order_fixed_amount_off",
 *   label = @Translation("Fixed amount off the order total"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderFixedAmountOff extends FixedAmountOffBase {

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $total_price = $order->getTotalPrice();
    $adjustment_amount = $this->getAmount();
    if ($total_price->getCurrencyCode() != $adjustment_amount->getCurrencyCode()) {
      return;
    }
    // Don't reduce the order total past zero.
    if ($adjustment_amount->greaterThan($total_price)) {
      $adjustment_amount = $total_price;
    }

    $order->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $adjustment_amount->multiply('-1'),
      'source_id' => $promotion->id(),
    ]));
  }

}
