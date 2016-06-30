<?php

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
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
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Constructs a new ProductVariationTypeForm object.
   *
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The attribute field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProductAttributeFieldManagerInterface $attribute_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->attributeFieldManager = $attribute_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_product.attribute_field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // Get all attribute-values for this product_variation.
    $field_map = $this->attributeFieldManager->getFieldMap($entity->bundle());

    // Do Drupal::entityQuery for the attributes-values.
    $this->entityTypeManager->getStorage('commerce_product_variation');
    $query = \Drupal::entityQuery('commerce_product_variation');

    // If result:count == 0: ok
    // If result:count == 1 && product_variation->id() == self-->id(): ok
    // Else ->addViolation()
  }

}
