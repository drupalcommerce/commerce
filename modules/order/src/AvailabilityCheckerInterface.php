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
   * Determines whether the checker applies to the given source entity.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source entity.
   *
   * @return bool
   *   TRUE if the checker applies to the given source entity, FALSE
   *   otherwise.
   */
  public function applies(LineItemSourceInterface $source);

  /**
   * Checks the availability of the given source entity.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source entity.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool|null
   *   TRUE if the source is available, FALSE if it is not available,
   *   or NULL if it has no opinion.
   */
  public function check(LineItemSourceInterface $source, $quantity = 1);

}
