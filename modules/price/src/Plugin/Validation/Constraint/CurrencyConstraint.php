<?php

namespace Drupal\commerce_price\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Currency constraint.
 *
 * @Constraint(
 *   id = "Currency",
 *   label = @Translation("Currency", context = "Validation"),
 *   type = { "commerce_price" }
 * )
 */
class CurrencyConstraint extends Constraint {

  public $availableCurrencies = [];
  public $invalidMessage = 'The Currency %value is not valid.';
  public $notAvailableMessage = 'The Currency %value is not available.';

}
