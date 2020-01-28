<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

class OrderTotalSummary implements OrderTotalSummaryInterface {

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * Constructs a new OrderTotalSummary object.
   *
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustment_transformer
   *   The adjustment transformer.
   */
  public function __construct(AdjustmentTransformerInterface $adjustment_transformer) {
    $this->adjustmentTransformer = $adjustment_transformer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTotals(OrderInterface $order) {
    $adjustments = $order->collectAdjustments();
    $adjustments = $this->adjustmentTransformer->processAdjustments($adjustments);
    // Included adjustments are not displayed to the customer, they
    // exist to allow the developer to know what the price is made of.
    // The one exception is taxes, which need to be shown for legal reasons.
    $adjustments = array_filter($adjustments, function (Adjustment $adjustment) {
      return $adjustment->getType() == 'tax' || !$adjustment->isIncluded();
    });
    // Convert the adjustments to arrays.
    $adjustments = array_map(function (Adjustment $adjustment) {
      return $adjustment->toArray();
    }, $adjustments);
    // Provide the "total" key for backwards compatibility reasons.
    foreach ($adjustments as $index => $adjustment) {
      $adjustments[$index]['total'] = $adjustments[$index]['amount'];
    }

    return [
      'subtotal' => $order->getSubtotalPrice(),
      'adjustments' => $adjustments,
      'total' => $order->getTotalPrice(),
    ];
  }

}
