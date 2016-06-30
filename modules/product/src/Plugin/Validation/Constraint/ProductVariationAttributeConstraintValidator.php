<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductVariationAttribute constraint.
 */
class ProductVariationAttributeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductVariationTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // Lookup variations with same attributes from same bundle.
    $query = \Drupal::entityQuery('commerce_product_variation');

    $attributes = $entity->getAttributeValueIds();
    $query->condition('type', $entity->bundle());
    foreach ($attributes as $field => $value) {
      $query->condition($field, $value);
    }
    $r = $query->execute();

    // New combination.
    if (count($r) == 0) {
      return;
    }
    // Combination exists, but it is this variation that is saved.
    if (count($r) == 1 && reset($r) == $entity->id()) {
      return;
    }
    // All other cases are violations.
    foreach ($attributes as $field => $value) {
      $this->context->buildViolation($constraint->message)
        ->atPath($field)
        ->addViolation();
    }
  }

}
