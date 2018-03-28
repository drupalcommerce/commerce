<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class ProductVariationAttributeMapper implements ProductVariationAttributeMapperInterface {

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The product attribute storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $attributeStorage;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ProductVariationAttributeMapper object.
   *
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The attribute field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(ProductAttributeFieldManagerInterface $attribute_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->attributeFieldManager = $attribute_field_manager;
    $this->attributeStorage = $entity_type_manager->getStorage('commerce_product_attribute');
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function selectVariation(array $variations, array $attribute_values = []) {
    $selected_variation = NULL;
    // Select the first variation that matches the most attribute values.
    // Start with all attribute values, reduce them by 1 until a match is found.
    while (!empty($attribute_values)) {
      foreach ($variations as $variation) {
        $match = TRUE;
        foreach ($attribute_values as $field_name => $attribute_value_id) {
          if ($variation->getAttributeValueId($field_name) != $attribute_value_id) {
            $match = FALSE;
          }
        }
        if ($match) {
          $selected_variation = $variation;
          break 2;
        }
      }
      array_pop($attribute_values);
    }

    return $selected_variation;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareAttributes(ProductVariationInterface $selected_variation, array $variations) {
    $attributes = [];
    $field_definitions = $this->attributeFieldManager->getFieldDefinitions($selected_variation->bundle());
    $field_map = $this->attributeFieldManager->getFieldMap($selected_variation->bundle());
    $field_names = array_column($field_map, 'field_name');
    $attribute_ids = array_column($field_map, 'attribute_id');
    $index = 0;
    foreach ($field_names as $field_name) {
      $field = $field_definitions[$field_name];
      /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
      $attribute = $this->attributeStorage->load($attribute_ids[$index]);
      // Make sure we have translation for attribute.
      $attribute = $this->entityRepository->getTranslationFromContext($attribute, $selected_variation->language()->getId());

      $definition = [
        'id' => $attribute->id(),
        'label' => $attribute->label(),
        'element_type' => $attribute->getElementType(),
        'required' => $field->isRequired(),
      ];
      // The first attribute gets all values. Every next attribute gets only
      // the values from variations matching the previous attribute value.
      // For 'Color' and 'Size' attributes that means getting the colors of all
      // variations, but only the sizes of variations with the selected color.
      $callback = NULL;
      if ($index > 0) {
        $index_limit = $index - 1;
        // Get all previous field values.
        $previous_variation_field_values = [];
        for ($i = 0; $i <= $index_limit; $i++) {
          $previous_variation_field_values[$field_names[$i]] = $selected_variation->getAttributeValueId($field_names[$i]);
        }

        $callback = function (ProductVariationInterface $variation) use ($previous_variation_field_values) {
          $results = [];
          foreach ($previous_variation_field_values as $previous_field_name => $previous_field_value) {
            $results[] = $variation->getAttributeValueId($previous_field_name) == $previous_field_value;
          }
          return !in_array(FALSE, $results, TRUE);
        };
      }
      $definition['values'] = $this->getAttributeValues($variations, $field_name, $callback);
      // Return the attribute only if it has at least one value.
      if (!empty($definition['values'])) {
        $attributes[$field_name] = new PreparedAttribute($definition);
      }

      $index++;
    }

    return $attributes;
  }

  /**
   * Gets the attribute values of a given set of variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param string $field_name
   *   The field name of the attribute.
   * @param callable|null $callback
   *   An optional callback to use for filtering the list.
   *
   * @return array[]
   *   The attribute values, keyed by attribute ID.
   */
  protected function getAttributeValues(array $variations, $field_name, callable $callback = NULL) {
    $values = [];
    foreach ($variations as $variation) {
      if (is_null($callback) || call_user_func($callback, $variation)) {
        $attribute_value = $variation->getAttributeValue($field_name);
        if ($attribute_value) {
          $values[$attribute_value->id()] = $attribute_value->label();
        }
        else {
          $values['_none'] = '';
        }
      }
    }

    return $values;
  }

}
