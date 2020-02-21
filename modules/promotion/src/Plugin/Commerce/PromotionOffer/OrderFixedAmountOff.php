<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the fixed amount off offer for orders.
 *
 * The discount is split between order items, to simplify VAT taxes and refunds.
 *
 * @CommercePromotionOffer(
 *   id = "order_fixed_amount_off",
 *   label = @Translation("Fixed amount off the order subtotal"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderFixedAmountOff extends OrderPromotionOfferBase {

  use FixedAmountOffTrait;

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $subtotal_price = $order->getSubTotalPrice();
    $amount = $this->getAmount();
    if ($subtotal_price->getCurrencyCode() != $amount->getCurrencyCode()) {
      return;
    }
    // The promotion amount can't be larger than the subtotal, to avoid
    // potentially having a negative order total.
    if ($amount->greaterThan($subtotal_price)) {
      $amount = $subtotal_price;
    }
    // Split the amount between order items.
    $amounts = $this->splitter->split($order, $amount);

    foreach ($order->getItems() as $order_item) {
      if (isset($amounts[$order_item->id()])) {
        $order_item->addAdjustment(new Adjustment([
          'type' => 'promotion',
          'label' => $promotion->getDisplayName() ?: $this->t('Discount'),
          'amount' => $amounts[$order_item->id()]->multiply('-1'),
          'source_id' => $promotion->id(),
        ]));
      }
    }
  }

}
