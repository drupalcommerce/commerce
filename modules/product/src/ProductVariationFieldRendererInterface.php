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
   *   The view mode.
   *
   * @return array
   *   Array of render arrays, keyed by field name.
   */
  public function renderFields(ProductVariationInterface $variation, $view_mode = 'default');

  /**
   * Renders a single variation field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param string|array $display_options
   *   Can be either:
   *   - The name of a view mode. The field will be displayed according to the
   *     display settings specified for this view mode in the $field
   *     definition for the field in the entity's bundle. If no display settings
   *     are found for the view mode, the settings for the 'default' view mode
   *     will be used.
   *   - An array of display options. The following key/value pairs are allowed:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type. The default formatter will
   *       also be used if the requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Defaults to the
   *       formatter's default settings.
   *     - weight: (float) The weight to assign to the renderable element.
   *       Defaults to 0.
   *
   * @return array
   *   The render array.
   */
  public function renderField($field_name, ProductVariationInterface $variation, $display_options = []);

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

}
