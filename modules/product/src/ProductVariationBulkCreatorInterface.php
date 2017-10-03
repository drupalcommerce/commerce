<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\Product;

/**
 * Manages variation combinations creation.
 *
 * Variation combination is a unique array of the variation attributes IDs.
 */
interface ProductVariationBulkCreatorInterface {

  /**
   * Helper method to get variation sku field form display settings.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $variation
   *   The commerce product variation.
   *
   * @return \Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationSkuWidget
   *   The product variation SKU widget.
   *
   * @see \Drupal\commerce\Plugin\Field\FieldWidget\ProductVariationSkuWidget
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget
   */
  public static function getSkuwidget(ProductVariation $variation);

  /**
   * Helper method to get variation sku field form display settings.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $variation
   *   The commerce product variation.
   *
   * @return array
   *   The last three elements are only present on ProductVariationSkuWidget:
   *   - "size": HTML size attribute value.
   *   - "placeholder": HTML placeholder attribute value.
   *   - "prefix": An optional prefix for the field value.
   *   - "suffix": An optional suffix for the field value.
   *   - "more_entropy": The length and therefore uniqueness of the field value.
   *
   * @see \Drupal\commerce\Plugin\Field\FieldWidget\ProductVariationSkuWidget
   * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget
   */
  public static function getSkuSettings(ProductVariation $variation);

  /**
   * Default value callback for the 'sku' base field definition.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $variation
   *   The commerce product variation.
   *
   * @return string
   *   An optionally prefixed/suffixed unique identifier based on settings
   *   of the widget of the field and current time in microseconds.
   *
   * @see \Drupal\commerce_product\Entity\ProductVariation::baseFieldDefinitions()
   * @see http://php.net/manual/en/function.uniqid.php
   */
  public static function getAutoSku(ProductVariation $variation);

  /**
   * A callback which might be set to #pre_render or #after_build form element.
   *
   * This helper function alters data on the form element passed along with the
   * element in the following manner:
   * @code
   * $i = 0;
   * $element['alter_data_' . $i] = [
   *   '#parents' => ['form', 'deep', 'nested', 'array_element'],
   *   '#default_value' => $my_value,
   *   // ...
   * ];
   * $i++;
   * $element['alter_data_' . $i] = [
   *   '#parents' => ['form', 'another', 'nested', 'array_element'],
   *   '#disabled' => TRUE,
   *   // ...
   * ];
   * // @var \Drupal\commerce_product\ProductVariationBulkCreator $creator
   * $element['#after_build'][] = [$creator, 'afterBuildPreRenderArrayAlter'];
   * @endcode
   * It is primarily used for form structures and renderable arrays. Any number
   * of data arrays with different paths (#parents) may be attached to an
   * element. If #parents is omitted the altering will apply on the root of the
   * element. The $creator may be passed to a callbacks array as an object or
   * a fully qualified class name. After the target array elements being altered
   * the 'alter_data_NNN' containers are unset.
   *
   * @param array $element
   *   The render array element normally passed by the system call.
   *
   * @return array
   *   The altered render array element.
   *
   * @see commerce_product_field_widget_form_alter()
   */
  public static function afterBuildPreRenderArrayAlter(array $element);

  /**
   * Gets a variation for commerce_product.
   *
   * @param \Drupal\commerce_product\Entity\Product $product
   *   A commerce product, whether new or having some variations saved on it.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariation
   *   If exists, the last variation on a commerce_product, otherwise new one.
   *
   * @see \Drupal\commerce_product\Entity\ProductVariation->create()
   * @see self->createProductVariation()
   */
  public function getProductVariation(Product $product);

  /**
   * Creates a variation for commerce_product.
   *
   * @param \Drupal\commerce_product\Entity\Product $product
   *   A commerce product, whether new or having some variations saved on it.
   * @param array $variation_custom_values
   *   (optional) An associative array of a variation property values which
   *   will be used to auto create sample variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariation
   *   A commerce_product variation.
   *
   * @see \Drupal\commerce_product\Entity\ProductVariation->create()
   * @see self->createAllProductVariations()
   */
  public function createProductVariation(Product $product, array $variation_custom_values = []);

  /**
   * Creates all possible variations for commerce_product.
   *
   * @param \Drupal\commerce_product\Entity\Product $product
   *   A commerce product, whether new or having some variations saved on it.
   * @param array $variation_custom_values
   *   (optional) An associative array of a variation property values which
   *   will be used to auto create all variations.
   *
   * @return array|null
   *   An array of all commerce product variations that were missed before.
   *
   * @see \Drupal\commerce_product\Entity\Product->getVariations()
   * @see self->getAllAttributesCombinations()
   */
  public function createAllProductVariations(Product $product, array $variation_custom_values = []);

  /**
   * An AJAX callback to create all possible variations on the commerce product
   * add or edit form.
   *
   * @param array $form
   *   An array form for commerce_product with ief widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the commerce_product form with at least one variation
   *   created.
   *
   * @see self->getIefFormAllAttributesCombinations()
   */
  public function createAllIefFormVariations(array $form, FormStateInterface $form_state);

  /**
   * Gets first not used combination on a product IEF form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the commerce_product form with at least one variation
   *   created.
   * @param string $ief_id
   *   A product form IEF widget id.
   *
   * @return array|null
   *   a number of possible and duplicated and used combinations, last
   *   variation, number of duplicated combinations and an HTML list of
   *   duplicated variations labels if they are found:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "count": The number of all combinations.
   *   - "duplicated": The number of duplicated combinations.
   *   - "used": The number of used combinations.
   *   - "duplications_list": HTML list of duplicated combinations if present.
   *   - "not_used_combination": The first not used attributes combination.
   */
  public function getIefFormNotUsedAttributesCombination(FormStateInterface $form_state, $ief_id = '');

  /**
   * Gets first not used combination on a product.
   *
   * @param array $variations
   *   The commerce product variations.
   *
   * @return array|null
   *   a number of possible and duplicated and used combinations, last
   *   variation, number of duplicated combinations and an HTML list of
   *   duplicated variations labels if they are found:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "count": The quantity of the combinations.
   *   - "duplicated": The number of duplicated combinations.
   *   - "used": The number of used combinations.
   *   - "duplications_list": HTML list of duplicated combinations if present.
   *   - "not_used_combination": The first not used attributes combination.
   */
  public function getNotUsedAttributesCombination(array $variations);

  /**
   * Gets used combinations on a product.
   *
   * @param array $variations
   *   The commerce product variations.
   *
   * @return array|null
   *   last variation, variation attributes ids and options and already used
   *   used attributes combinations, if they are found:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "attributes": An array with attributes ids and options:
   *     - "ids": The array of field_name => id pairs.
   *     - "options": The array of id => field_label pairs.
   *   - "used_combinations": The already used attributes combinations.
   */
  public function getUsedAttributesCombinations(array $variations);

  /**
   * Gets duplicated variations HTML list.
   *
   * @param array $variations
   *   The commerce product variations.
   *
   * @return array|null
   *   An array of used combinations, not used combinations and their number,
   *   last variation, variation attributes ids and options, and an HTML list of
   *   duplicated variations labels if they are found:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "used_combinations": The already used combinations.
   *   - "duplicated": The number of duplicated combinations.
   *   - "used": The number of used combinations.
   *   - "duplications_list": HTML list of duplicated combinations if present.
   *   - "attributes": An array with attributes ids and options:
   *     - "ids": The array of field_name => id pairs.
   *     - "options": The array of id => field_label pairs.
   *   - "not_all": The maximum number of combinations to return.
   */
  public function getDuplicationsHtmlList(array $variations);

  /**
   * Gets all ids combinations of the commerce_product's attribute fields.
   *
   * @param array $variations
   *   The commerce product variations.
   * @param array $return
   *   (optional) Whether to return all attributes combinations or just ~ 500.
   *
   * @return array|null
   *   An array of used combinations, not used combinations and their number,
   *   last variation, variation attributes ids and options:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "used_combinations": The already used combinations.
   *   - "not_used_combinations": Yet not used combinations.
   *   - "count": The number of all combinations.
   *   - "attributes": An array with attributes ids and options:
   *     - "ids": The array of field_name => id pairs.
   *     - "options": The array of id => field_label pairs.
   *   - "duplicated": The number of duplicated combinations.
   *   - "used": The number of used combinations.
   *   - "not_all": The maximum number of combinations to return.
   */
  public function getAttributesCombinations(array $variations, array $return = ['not_all' => 500]);

  /**
   * Gets combinations of an Array values.
   *
   * See the function
   * @link https://gist.github.com/fabiocicerchia/4556892 source origin @endlink
   * .
   *
   * @param array $data
   *   An array with mixed data.
   * @param array $exclude
   *   (optional) An array with mixed data to exclude from the return.
   *
   *   An array of used combinations, not used combinations and their number,
   *   last variation, variation attributes ids and options:
   *   - "last_variation": The variation on the last inline entity form array.
   *   - "used_combinations": The already used combinations.
   *   - "not_used_combinations": Yet not used combinations.
   *   - "count": The number of all combinations.
   *   - "attributes": An array with attributes ids and options:
   *     - "ids": The array of field_name => id pairs.
   *     - "options": The array of id => field_label pairs.
   *   - "duplicated": The number of duplicated combinations.
   *   - "used": The number of used combinations.
   *     combinations.
   */
  public function getArrayValueCombinations(array $data = [], array $exclude = [], &$all = [], $group = [], $value = NULL, $i = 0, $k = NULL, $c = NULL, $f = NULL);

  /**
   * Gets the IDs of the variation's attribute fields.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $variation
   *   The commerce product variation.
   *
   * @return array
   *   An array of IDs arrays keyed by field name.
   */
  public function getAttributeFieldOptionIds(ProductVariation $variation);

  /**
   * Gets the names of the entity's attribute fields.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariation $variation
   *   The commerce product variation.
   *
   * @return string[]
   *   The attribute field names.
   */
  public function getAttributeFieldNames(ProductVariation $variation);

}
