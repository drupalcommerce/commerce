<?php

/**
 * @file
 * Contains \Drupal\commerce_order\AvailabilityManagerInterface.
 */

namespace Drupal\commerce_order;

use Drupal\commerce\LineItemSourceInterface;

/**
 * Defines the interface for availability managers.
 */
interface AvailabilityManagerInterface {

  /**
   * Checks availability of a source with the registered availability checkers.
   *
   * @param \Drupal\commerce\LineItemSourceInterface $source
   *   The source.
   * @param int $quantity
   *   The quantity.
   *
   * @return bool
   *   Returns TRUE if the source is available, FALSE if it is not available.
   */
  public function check(LineItemSourceInterface $source, $quantity);

}
