<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class ProductVariationFieldRenderer implements ProductVariationFieldRendererInterface {

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->variationViewBuilder = $entity_type_manager->getViewBuilder('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public function renderFields(ProductVariationInterface $variation, $view_mode = 'default') {
    $build = $this->variationViewBuilder->view($variation, $view_mode);
    // Formatters aren't called until #pre_render.
    foreach ($build['#pre_render'] as $callable) {
      $build = call_user_func($callable, $build);
    }
    unset($build['#pre_render']);
    // Rendering the product can cause an infinite loop.
    unset($build['product_id']);
    // Fields are rendered individually, top-level properties are not needed.
    foreach (array_keys($build) as $key) {
      if (Element::property($key)) {
        unset($build[$key]);
      }
    }
    // Prepare the fields for AJAX replacement.
    foreach ($build as $field_name => $rendered_field) {
      $build[$field_name] = $this->prepareForAjax($rendered_field, $field_name, $variation);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderField($field_name, ProductVariationInterface $variation, $display_options = []) {
    $rendered_field = $this->variationViewBuilder->viewField($variation->get($field_name), $display_options);
    // An empty array indicates that the field is hidden on the view display.
    if (!empty($rendered_field)) {
      $rendered_field = $this->prepareForAjax($rendered_field, $field_name, $variation);
    }

    return $rendered_field;
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
   * Prepares the rendered field for AJAX replacement.
   *
   * @param array $rendered_field
   *   The rendered field.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return array
   *   The prepared rendered field.
   */
  protected function prepareForAjax(array $rendered_field, $field_name, ProductVariationInterface $variation) {
    $ajax_class = $this->buildAjaxReplacementClass($field_name, $variation);
    $rendered_field['#attributes']['class'][] = $ajax_class;
    $rendered_field['#ajax_replace_class'] = $ajax_class;
    // Ensure that a <div> is rendered even if the field is empty, to allow
    // field replacement to work when the variation changes.
    if (!Element::children($rendered_field)) {
      $rendered_field['#type'] = 'container';
    }

    return $rendered_field;
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

}
