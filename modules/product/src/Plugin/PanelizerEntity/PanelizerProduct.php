<?php

namespace Drupal\commerce_product\Plugin\PanelizerEntity;

use Drupal\commerce_product\ProductVariationFieldRenderer;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\panelizer\Plugin\PanelizerEntityBase;
use Drupal\panels\PanelsDisplayManager;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Panelizer entity plugin for integrating with products.
 *
 * @PanelizerEntity("commerce_product")
 */
class PanelizerProduct extends PanelizerEntityBase {

  /**
   * The product field variation renderer.
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRenderer
   */
  protected $variationFieldRenderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PanelizerProduct object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Panels\PanelsDisplayManager $panels_manager
   *   The Panels display manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\commerce_product\ProductVariationFieldRenderer $variation_field_renderer
   *   The product variation field renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PanelsDisplayManager $panels_manager, EntityFieldManagerInterface $entity_field_manager, ProductVariationFieldRenderer $variation_field_renderer, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $panels_manager, $entity_field_manager);

    $this->variationFieldRenderer = $variation_field_renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('panels.display_manager'),
      $container->get('entity_field.manager'),
      $container->get('commerce_product.variation_field_renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultDisplay(EntityViewDisplayInterface $display, $bundle, $view_mode) {
    $panels_display = parent::getDefaultDisplay($display, $bundle, $view_mode)->setPageTitle('[commerce_product:title]');

    // Remove the 'title' block because it's covered already.
    foreach ($panels_display->getRegionAssignments() as $region => $blocks) {
      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_id => $block) {
        if ($block->getPluginId() == 'entity_field:commerce_product:title') {
          $panels_display->removeBlock($block_id);
        }
      }
    }

    return $panels_display;
  }

  /**
   * {@inheritdoc}
   *
   * Currently this mimics \Drupal\commerce_product\ProductViewBuilder::alterBuild
   * until we expose injected variation fields to Panels.
   *
   * @todo Remove once https://www.drupal.org/node/2723691 lands
   */
  public function alterBuild(array &$build, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    parent::alterBuild($build, $entity, $panels_display, $view_mode);

    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $product_type_storage->load($entity->bundle());
    if ($product_type->shouldInjectVariationFields() && $entity->getDefaultVariation()) {
      $variation = $variation_storage->loadFromContext($entity);
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
