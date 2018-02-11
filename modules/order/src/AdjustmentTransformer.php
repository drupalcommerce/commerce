<?php

namespace Drupal\commerce_order;

use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Utility\SortArray;

class AdjustmentTransformer implements AdjustmentTransformerInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new AdjustmentTransformer object.
   *
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   */
  public function __construct(AdjustmentTypeManager $adjustment_type_manager, RounderInterface $rounder) {
    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public function processAdjustments(array $adjustments) {
    $adjustments = $this->combineAdjustments($adjustments);
    $adjustments = $this->sortAdjustments($adjustments);
    $adjustments = $this->roundAdjustments($adjustments);

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function combineAdjustments(array $adjustments) {
    $combined_adjustments = [];
    foreach ($adjustments as $index => $adjustment) {
      $type = $adjustment->getType();
      $source_id = $adjustment->getSourceId();
      if (empty($source_id)) {
        // Adjustments without a source ID are always shown standalone.
        $key = $index;
      }
      else {
        // Adjustments with the same type and source ID are combined.
        $key = $type . '_' . $source_id;
      }

      if (empty($combined_adjustments[$key])) {
        $combined_adjustments[$key] = $adjustment;
      }
      else {
        $combined_adjustments[$key] = $combined_adjustments[$key]->add($adjustment);
      }
    }
    // The keys used for combining are irrelevant to the caller.
    $combined_adjustments = array_values($combined_adjustments);

    return $combined_adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function sortAdjustments(array $adjustments) {
    $types = $this->adjustmentTypeManager->getDefinitions();
    $data = [];
    foreach ($adjustments as $adjustment) {
      $data[] = [
        'adjustment' => $adjustment,
        'weight' => $types[$adjustment->getType()]['weight'],
      ];
    }
    uasort($data, [SortArray::class, 'sortByWeightElement']);
    // Re-extract the adjustments from the sorted array.
    $adjustments = array_column($data, 'adjustment');

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function roundAdjustments(array $adjustments, $mode = PHP_ROUND_HALF_UP) {
    foreach ($adjustments as $index => $adjustment) {
      $adjustments[$index] = $this->roundAdjustment($adjustment, $mode);
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function roundAdjustment(Adjustment $adjustment, $mode = PHP_ROUND_HALF_UP) {
    $amount = $this->rounder->round($adjustment->getAmount(), $mode);
    $adjustment = new Adjustment([
      'amount' => $amount,
    ] + $adjustment->toArray());

    return $adjustment;
  }

}
