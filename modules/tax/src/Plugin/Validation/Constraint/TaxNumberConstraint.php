<?php

namespace Drupal\commerce_tax\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a vat number format is valid
 *
 * @Constraint(
 *   id = "TaxNumber",
 *   label = @Translation("Check if vat number format is valid", context =
 *   "Validation"),
 * )
 */
class TaxNumberConstraint extends Constraint {

  /**
   * Violation message.
   *
   * @var string
   */
  public $incorrectFormat = "The format of the specified tax number is not recognized.";

}
