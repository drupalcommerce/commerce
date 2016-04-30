<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Renders variation fields.
 *
 * The rendered variation fields are displayed along the parent product fields.
 * Optionally replaced via AJAX when the add to cart form changes the selected
 * variation.
 */
interface ProductVariationFieldRendererInterface {

  /**
   * Gets the renderable field definitions for the given variation type.
   *
   * @param string $variation_type_id
   *   The product variation type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The renderable field definitions, keyed by field name.
   */
  public function getFieldDefinitions($variation_type_id);

  /**
   * Renders all renderable variation fields.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param string $view_mode
   *   The display mode.
   *
   * @return array
   *    Array of render arrays, keyed by field name.
   */
  public function renderFields(ProductVariationInterface $variation, $view_mode = 'default');

  /**
   * Renders a single variation field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation entity.
   * @param string $view_mode
   *   The display mode.
   *
   * @return array
   *    The render array.
   */
  public function renderField($field_name, ProductVariationInterface $variation, $view_mode = 'default');

  /**
   * Replaces the rendered variation fields via AJAX.
   *
   * Called by the add to cart form when the selected variation changes.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param string $view_mode
   *   The display mode.
   */
  public function replaceRenderedFields(AjaxResponse $response, ProductVariationInterface $variation, $view_mode = 'default');

  /**
   * Gets the AJAX replacement CSS class for a variation's field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $product_id
   *   The variation's product ID.
   *
   * @return string
   *   The CSS class.
   */
  public function getAjaxReplacementClass($field_name, $product_id);

}
