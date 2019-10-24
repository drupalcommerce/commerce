<?php

namespace Drupal\commerce_order\Comparator;

use Drupal\commerce_order\Adjustment;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * Provides a PHPUnit comparator for adjustments.
 */
class AdjustmentComparator extends Comparator {

  /**
   * {@inheritdoc}
   */
  public function accepts($expected, $actual) {
    return $expected instanceof Adjustment && $actual instanceof Adjustment;
  }

  /**
   * {@inheritdoc}
   */
  public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = FALSE, $ignoreCase = FALSE) {
    assert($expected instanceof Adjustment);
    assert($actual instanceof Adjustment);
    $expected_array = $expected->toArray();
    $actual_array = $actual->toArray();
    unset($expected_array['amount'], $actual_array['amount']);
    if (!$actual->getAmount()->equals($expected->getAmount()) || $expected_array !== $actual_array) {
      throw new ComparisonFailure(
        $expected,
        $actual,
        var_export($expected, TRUE),
        var_export($actual, TRUE),
        FALSE,
        sprintf('Failed asserting that Adjustment "%s" matches expected "%s".', $actual->getLabel(), $expected->getLabel())
      );
    }
  }

}
