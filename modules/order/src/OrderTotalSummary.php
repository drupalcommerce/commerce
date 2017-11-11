<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\SortArray;

class OrderTotalSummary implements OrderTotalSummaryInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AdjustmentTypeManager $adjustment_type_manager) {
    $this->adjustmentTypeManager = $adjustment_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTotals(OrderInterface $order) {
    $types = $this->adjustmentTypeManager->getDefinitions();
    $adjustments = [];
    foreach ($order->collectAdjustments() as $adjustment) {
      $type = $adjustment->getType();
      $source_id = $adjustment->getSourceId();
      if (empty($source_id)) {
        // Adjustments without a source ID are always shown standalone.
        $key = count($adjustments);
      }
      else {
        // Adjustments with the same type and source ID are combined.
        $key = $type . '_' . $source_id;
      }

      if (empty($adjustments[$key])) {
        $adjustments[$key] = [
          'type' => $type,
          'label' => $adjustment->getLabel(),
          'total' => $adjustment->getAmount(),
          'percentage' => $adjustment->getPercentage(),
          'weight' => $types[$type]['weight'],
        ];
      }
      else {
        $adjustments[$key]['total'] = $adjustments[$key]['total']->add($adjustment->getAmount());
      }
    }
    // Sort the adjustments by weight.
    uasort($adjustments, [SortArray::class, 'sortByWeightElement']);

    return [
      'subtotal' => $order->getSubtotalPrice(),
      'adjustments' => $adjustments,
      'total' => $order->getTotalPrice(),
    ];
  }

}
