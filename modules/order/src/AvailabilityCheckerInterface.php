<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityCheckerInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\LineItemSourceInterface;

/**
 * Defines the interface for availability checkers.
 */
interface AvailabilityCheckerInterface {

  /**
   * Determines whether the checker can be used with the given source entity.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source.
   *
   * @return bool
   *   Returns TRUE if the given source entity can be used with the checker,
   *   returns FALSE if it cannot.
   */
  public function applies(LineItemSourceInterface $source);

  /**
   * Checks the availability of a given source and quantity.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool
   *   Returns TRUE if the source is available, FALSE if it is not available,
   *   or NULL if it has no opinion on the source.
   */
  public function check(LineItemSourceInterface $source, $quantity);

}
