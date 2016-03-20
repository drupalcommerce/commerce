<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductAttributeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Default implementation of the ProductAttributeFieldManagerInterface.
 */
class ProductAttributeFieldManager implements ProductAttributeFieldManagerInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Local cache for attribute field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fieldDefinitions = [];

  /**
   * Local cache for the attribute field map.
   *
   * @var array
   */
  protected $fieldMap;

  /**
   * Constructs a new ProductAttributeFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, CacheBackendInterface $cache) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($variation_type_id) {
    if (!isset($this->fieldDefinitions[$variation_type_id])) {
      $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
      $definitions = array_filter($definitions, function ($definition) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
        $field_type = $definition->getType();
        $target_type = $definition->getSetting('target_type');
        return $field_type == 'entity_reference' && $target_type == 'commerce_product_attribute_value';
      });
      $this->fieldDefinitions[$variation_type_id] = $definitions;
    }

    return $this->fieldDefinitions[$variation_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap($variation_type_id = NULL) {
    if (!isset($this->fieldMap)) {
      if ($cached_map = $this->cache->get('commerce_product.attribute_field_map')) {
        $this->fieldMap = $cached_map->data;
      }
      else {
        $this->fieldMap = $this->buildFieldMap();
        $this->cache->set('commerce_product.attribute_field_map', $this->fieldMap);
      }
    }

    if ($variation_type_id) {
      // The map is empty for any variation type that has no attribute fields.
      return isset($this->fieldMap[$variation_type_id]) ? $this->fieldMap[$variation_type_id] : [];
    }
    else {
      return $this->fieldMap;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCaches() {
    $this->fieldDefinitions = [];
    $this->fieldMap = NULL;
    $this->cache->delete('commerce_product.attribute_field_map');
  }

  /**
   * Builds the field map.
   *
   * @return array
   *   The built field map.
   */
  protected function buildFieldMap() {
    $field_map = [];
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('commerce_product_variation');
    foreach (array_keys($bundle_info) as $bundle) {
      foreach ($this->getFieldDefinitions($bundle) as $field_name => $definition) {
        $handler_settings = $definition->getSetting('handler_settings');
        $field_map[$bundle][] = [
          'attribute_id' => reset($handler_settings['target_bundles']),
          'field_name' => $field_name,
        ];
      }
    }

    return $field_map;
  }

  /**
   * {@inheritdoc}
   */
  public function createField(ProductAttributeInterface $attribute, $variation_type_id) {
    $field_name = $this->buildFieldName($attribute);
    $field_storage = FieldStorageConfig::loadByName('commerce_product_variation', $field_name);
    $field = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'type' => 'entity_reference',
        'cardinality' => 1,
        'settings' => [
          'target_type' => 'commerce_product_attribute_value',
        ],
        'locked' => TRUE,
        'translatable' => FALSE,
      ]);
      $field_storage->save();
    }
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $variation_type_id,
        'label' => $attribute->label(),
        'required' => TRUE,
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            'target_bundles' => [$attribute->id()],
          ],
        ],
        'translatable' => FALSE,
      ]);
      $field->save();

      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $form_display */
      $form_display = commerce_get_entity_display('commerce_product_variation', $variation_type_id, 'form');
      $form_display->setComponent($field_name, [
        'type' => 'options_select',
        'weight' => 1,
      ]);
      $form_display->save();

      $this->clearCaches();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canDeleteField(ProductAttributeInterface $attribute) {
    $field_name = $this->buildFieldName($attribute);
    $field_storage = FieldStorageConfig::loadByName('commerce_product_variation', $field_name);
    return !$field_storage->hasData();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteField(ProductAttributeInterface $attribute, $variation_type_id) {
    if (!$this->canDeleteField($attribute)) {
      return;
    }

    $field_name = $this->buildFieldName($attribute);
    $field = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
    if ($field) {
      $field->delete();
      $this->clearCaches();
    }
  }

  /**
   * Builds the field name for the given attribute.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   *
   * @return string
   *   The field name.
   */
  protected function buildFieldName(ProductAttributeInterface $attribute) {
    return 'attribute_' . $attribute->id();
  }

}
