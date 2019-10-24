<?php

namespace Drupal\commerce_price\Comparator;

use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * Provides a PHPUnit comparator for numbers cast to strings.
 *
 * In PHPUnit 6, $this->assertEquals('2.0', '2.000') would pass because
 * numerically the two strings were equal. This behavior was removed in
 * PHPUnit 7, and the assert fails. This comparator restores the ability to
 * compare two strings numerically.
 */
class NumberComparator extends Comparator {

  /**
   * {@inheritdoc}
   */
  public function accepts($expected, $actual) {
    return is_string($expected) && is_numeric($expected) && is_string($actual) && is_numeric($actual);
  }

  /**
   * {@inheritdoc}
   */
  public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = FALSE, $ignoreCase = FALSE) {
    if ($expected != $actual) {
      throw new ComparisonFailure(
        $expected,
        $actual,
        '',
        '',
        FALSE,
        sprintf('Failed asserting that "%s" matches expected "%s".', $actual, $expected)
      );
    }
  }

}
