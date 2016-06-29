<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductVariationAttribute constraint.
 */
class ProductVariationAttributeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_product.attribute_field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // Get all attribute-values for this product_variation.
    $attribute_field_manager = \Drupal::service('commerce_product.attribute_field_manager');
    $field_map = $attribute_field_manager->getFieldMap($entity->bundle());

    // Do Drupal::entityQuery for the attributes-values.
    // If result:count == 0: ok
    // If result:count == 1 && product_variation->id() == self-->id(): ok
    // Else ->addViolation()
  }

}
