<?php

namespace Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern;

use Drupal\commerce_number_pattern\Sequence;

/**
 * Provides the infinite number pattern.
 *
 * @CommerceNumberPattern(
 *   id = "infinite",
 *   label = @Translation("Infinite (Never reset)"),
 * )
 */
class Infinite extends SequentialNumberPatternBase {

  /**
   * {@inheritdoc}
   */
  protected function shouldReset(Sequence $current_sequence) {
    return FALSE;
  }

}
