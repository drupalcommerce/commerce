<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;

class ProductVariationFieldRenderer implements ProductVariationFieldRendererInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The product variation view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $variationViewBuilder;

  /**
   * Constructs a new ProductVariationFieldRenderer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->variationViewBuilder = $entity_type_manager->getViewBuilder('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($variation_type_id) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
    $allowed_base_fields = $this->getAllowedBaseFields();
    foreach ($definitions as $field_name => $definition) {
      if ($definition instanceof BaseFieldDefinition && !in_array($field_name, $allowed_base_fields)) {
        unset($definitions[$field_name]);
      }
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function renderFields(ProductVariationInterface $variation, $view_mode = 'default') {
    $rendered_fields = [];
    foreach ($this->getFieldDefinitions($variation->bundle()) as $field_name => $field_definition) {
      $rendered_fields[$field_name] = $this->renderField($field_name, $variation, $view_mode);
    }

    return $rendered_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function renderField($field_name, ProductVariationInterface $variation, $display_options = []) {
    $ajax_class = $this->buildAjaxReplacementClass($field_name, $variation);
    $content = $this->variationViewBuilder->viewField($variation->get($field_name), $display_options);
    $content['#attributes']['class'][] = $ajax_class;
    $content['#ajax_replace_class'] = $ajax_class;

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceRenderedFields(AjaxResponse $response, ProductVariationInterface $variation, $view_mode = 'default') {
    $rendered_fields = $this->renderFields($variation, $view_mode);
    foreach ($rendered_fields as $field_name => $rendered_field) {
      $response->addCommand(new ReplaceCommand('.' . $rendered_field['#ajax_replace_class'], $rendered_field));
    }
  }

  /**
   * Builds the AJAX replacement CSS class for a variation's field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The CSS class.
   */
  protected function buildAjaxReplacementClass($field_name, ProductVariationInterface $variation) {
    return 'product--variation-field--variation_' . $field_name . '__' . $variation->getProductId();
  }

  /**
   * Gets the allowed base field definitions for injection.
   *
   * @return array
   *   An array of base field names.
   */
  protected function getAllowedBaseFields() {
    return ['title', 'sku', 'price'];
  }

}
