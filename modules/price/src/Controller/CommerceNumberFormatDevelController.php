<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Controller\CommerceNumberFormatDevelController.
 */

namespace Drupal\commerce_price\Controller;

use CommerceGuys\Intl\NumberFormat\NumberFormatInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Currency devel routes.
 */
class CommerceNumberFormatDevelController extends DevelController {

  /**
   * Dump devel information for a commerce_number_format entity.
   *
   * @param \CommerceGuys\Intl\NumberFormat\NumberFormatInterface $commerceNumberFormat
   *   The number format.
   *
   * @return string
   */
  public function numberFormatLoad(NumberFormatInterface $commerceNumberFormat) {
    return $this->loadObject('commerce_number_format', $commerceNumberFormat);
  }

}
