<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Plugin\Block\VariationFieldBlock;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\LayoutEntityHelperTrait;

class ProductVariationFieldRendererLayoutBuilder extends ProductVariationFieldRenderer {

  use LayoutEntityHelperTrait;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new ProductVariationFieldRendererLayoutBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($entity_type_manager);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function renderFields(ProductVariationInterface $variation, $view_mode = 'default') {
    // Get parent product and load its view mode display.
    $product = $variation->getProduct();
    assert($product !== NULL);
    $view_mode_display = $this->entityDisplayRepository->getViewDisplay($product->getEntityTypeId(), $product->bundle());
    // Check if layouts are enabled for that product.
    if ($view_mode_display instanceof LayoutBuilderEntityViewDisplay && $view_mode_display->isLayoutBuilderEnabled()) {
      // Grab sections from bundle layout view mode.
      $sections = $view_mode_display->getSections();

      // If overrides are allowed, fetch them if they exists.
      if ($view_mode_display->isOverridable() && $overrides = $this->getEntitySections($variation->getProduct())) {
        $sections = $overrides;
      }

      // Render fields for output.
      return $this->renderLayoutBuilderFields($variation, $sections);
    }

    // If no layouts are enabled proceed to regular rendering.
    return parent::renderFields($variation, $view_mode);
  }

  /**
   * Render fields from LayoutBuilder sections.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections.
   *
   * @return array
   *   Return array of rendered fields.
   */
  protected function renderLayoutBuilderFields(ProductVariationInterface $variation, array $sections) {
    $build = [];

    // Loop trough sections, grab their components.
    foreach ($sections as $section) {

      // Grab section components, then loop trough them
      // to find fields from variations on each component.
      $components = $section->getComponents();
      foreach ($components as $component) {
        $plugin = $component->getPlugin();

        // We are only interested in field blocks from commerce product module.
        if ($plugin instanceof VariationFieldBlock) {
          $plugin_id = $plugin->getPluginId();

          list(,,, $field_name) = explode(PluginBase::DERIVATIVE_SEPARATOR, $plugin_id, 4);
          $display_options = $plugin->getConfiguration()['formatter'];

          // Render field with display options provided from plugin formatter.
          $build[$field_name] = $this->prepareForAjax($this->renderField($field_name, $variation, $display_options), $field_name, $variation);
        }
      }
    }

    return $build;
  }

}
