<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;

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
    $total = $order->getTotalPrice();
    $subtotal = $order->getSubtotalPrice();
    $collected_adjustments = $order->collectAdjustments();
    $types = $this->adjustmentTypeManager->getDefinitions();

    $adjustments = [];
    foreach ($collected_adjustments as $adjustment) {
      /** @var \Drupal\commerce_price\Price $amount */
      $amount = $adjustment['amount'];
      $type = $adjustment['type'];
      $weight = $types[$type]['weight'];
      $label = $types[$type]['label'];

      // Group adjustments by type.
      if (empty($adjustments[$type])) {
        $adjustments[$type] = [
          'label' => $label,
          'weight' => $weight,
          'items' => [],
        ];
      }

      // Add adjustment items, combining those with the same type and source id.
      if (empty($adjustment['source_id'])) {
        $adjustments[$type]['items'][] = [
          'label' => $adjustment['label'],
          'amount' => $amount,
        ];
      }
      else {
        $key = "{$type}__{$adjustment['source_id']}";
        if (empty($adjustments[$type]['items'][$key])) {
          $adjustments[$type]['items'][$key] = [
            'label' => $adjustment['label'],
            'amount' => $amount,
          ];
        }
        else {
          $adjustments[$type]['items'][$key]['amount'] = $adjustments[$type][$key]['amount']->add($amount);
        }
      }
    }

    // Sort each group by adjustment type weight then remove weight property.
    $type_order = [];
    foreach ($adjustments as $id => $type) {
      $type_order[$id] = $type['weight'];
    }
    array_multisort($type_order, SORT_ASC, $adjustments);
    foreach ($adjustments as &$group) {
      unset($group['weight']);
    }

    return [
      'total' => $total,
      'subtotal' => $subtotal,
      'adjustments' => $adjustments,
    ];
  }

}
