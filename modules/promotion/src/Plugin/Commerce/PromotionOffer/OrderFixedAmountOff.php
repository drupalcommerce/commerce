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
 *   label = @Translation("Fixed amount off the order subtotal"),
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
    $subtotal_price = $order->getSubTotalPrice();
    $adjustment_amount = $this->getAmount();
    if ($subtotal_price->getCurrencyCode() != $adjustment_amount->getCurrencyCode()) {
      return;
    }
    // The promotion amount can't be larger than the subtotal, to avoid
    // potentially having a negative order total.
    if ($adjustment_amount->greaterThan($subtotal_price)) {
      $adjustment_amount = $subtotal_price;
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
