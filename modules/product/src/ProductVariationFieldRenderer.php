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
    foreach ($build as $key => $value) {
      if (Element::property($key)) {
        unset($build[$key]);
      }
    }
    // Prepare the fields for AJAX replacement.
    foreach ($build as $field_name => &$elements) {
      $ajax_class = $this->buildAjaxReplacementClass($field_name, $variation);
      $elements['#attributes']['class'][] = $ajax_class;
      $elements['#ajax_replace_class'] = $ajax_class;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderField($field_name, ProductVariationInterface $variation, $display_options = []) {
    $build = $this->variationViewBuilder->viewField($variation->get($field_name), $display_options);
    if (!empty($build)) {
      // Prepare the fields for AJAX replacement.
      $ajax_class = $this->buildAjaxReplacementClass($field_name, $variation);
      $build['#attributes']['class'][] = $ajax_class;
      $build['#ajax_replace_class'] = $ajax_class;
    }

    return $build;
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

}
