<?php

namespace Drupal\commerce_order\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the order version.
 *
 * @Constraint(
 *   id = "OrderVersion",
 *   label = @Translation("Order version", context = "Validation"),
 *   type = "entity:commerce_order"
 * )
 */
class OrderVersionConstraint extends Constraint {

  public $message = 'The order has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';

}
