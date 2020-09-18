<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the entity view builder for products.
 */
class ProductViewBuilder extends EntityViewBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The product field variation renderer.
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRenderer
   */
  protected $variationFieldRenderer;

  /**
   * Constructs a new ProductViewBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_product\ProductVariationFieldRenderer $variation_field_renderer
   *   The product variation field renderer.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager, ProductVariationFieldRenderer $variation_field_renderer) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);

    $this->entityTypeManager = $entity_type_manager;
    $this->variationFieldRenderer = $variation_field_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('commerce_product.variation_field_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    // If we're in a Layout Builder controlled display, we do not need to run
    // variation field injection, as any of these fields will be placed as
    // blocks within the display.
    $is_layout_builder = $display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled();
    if ($is_layout_builder) {
      return;
    }
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $product_type_storage->load($entity->bundle());
    if ($product_type->shouldInjectVariationFields() && $entity->getDefaultVariation()) {
      $variation = $variation_storage->loadFromContext($entity);
      $variation = $this->entityRepository->getTranslationFromContext($variation, $entity->language()->getId());
      $attribute_field_names = $variation->getAttributeFieldNames();
      $rendered_fields = $this->variationFieldRenderer->renderFields($variation, $view_mode);
      foreach ($rendered_fields as $field_name => $rendered_field) {
        // Group attribute fields to allow them to be excluded together.
        if (in_array($field_name, $attribute_field_names)) {
          $build['variation_attributes']['variation_' . $field_name] = $rendered_field;
        }
        else {
          $build['variation_' . $field_name] = $rendered_field;
        }
      }
    }
  }

}
