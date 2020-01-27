<?php

namespace Drupal\commerce_order\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Purchasable entity available reference constraint.
 *
 * @Constraint(
 *   id = "PurchasedEntityAvailable",
 *   label = @Translation("Purchasable entity available", context = "Validation")
 * )
 */
class PurchasedEntityAvailableConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = '%label is not available with a quantity of %quantity.';

  /**
   * {@inheritdoc}
   *
   * This is overridden to make extending the constraint plugin easier. It
   * simplifies the ability to customize the $message property without having
   * to override this method and define the constraint validator.
   */
  public function validatedBy() {
    return PurchasedEntityAvailableConstraintValidator::class;
  }

}
