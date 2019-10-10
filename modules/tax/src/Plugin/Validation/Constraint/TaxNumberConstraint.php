<?php

namespace Drupal\commerce_tax\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Tax number constraint.
 *
 * @Constraint(
 *   id = "TaxNumber",
 *   label = @Translation("Tax number", context = "Validation"),
 *   type = { "commerce_tax_number" }
 * )
 */
class TaxNumberConstraint extends Constraint {

  public $verify = TRUE;
  public $allowUnverified = FALSE;
  public $invalidMessage = '%name is not in the right format.';
  public $invalidMessageWithExamples = '%name is not in the right format. @examples';
  public $verificationFailedMessage = '%name could not be verified.';

}
