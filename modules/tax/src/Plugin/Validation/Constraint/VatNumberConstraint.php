<?php

namespace Drupal\commerce_tax\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a vat number format is valid
 *
 * @Constraint(
 *   id = "VatNumber",
 *   label = @Translation("Check if vat number format is valid", context =
 *   "Validation"),
 * )
 */
class VatNumberConstraint extends Constraint {

  /**
   * Violation message.
   *
   * @var string
   */
  public $incorrectFormat = "Your current VAT number is incorrect.";

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
  public $vatNotValid = "Your vat number is not valid.";

}
