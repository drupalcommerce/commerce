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

  /**
   * Violation message.
   *
   * @var string
   */
  public $countryNotMatching = "Country code on billing address and VAT number Country code not matching.";

  /**
   * Violation message.
   *
   * @var string
   */
  public $notValid = "Your VAT number is not valid.";

}
