<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common configuration for the product category conditions.
 */
trait ProductCategoryTrait {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'terms' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $terms = NULL;
    $ids = $this->getTermIds();
    if (!empty($ids)) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadMultiple($ids);
    }
    $form['terms'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Categories'),
      '#default_value' => $terms,
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => $this->getVocabularyIds(),
      ],
      '#tags' => TRUE,
      '#required' => TRUE,
      '#maxlength' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Convert selected IDs into UUIDs, and store them.
    $values = $form_state->getValue($form['#parents']);
    $term_ids = array_column($values['terms'], 'target_id');
    $this->configuration['terms'] = $this->entityUuidMapper->mapFromIds('taxonomy_term', $term_ids);
    $this->configuration['terms'] = array_values($this->configuration['terms']);
  }

  /**
   * Gets the configured term IDs.
   *
   * @return array
   *   The term IDs.
   */
  protected function getTermIds() {
    return $this->entityUuidMapper->mapToIds('taxonomy_term', $this->configuration['terms']);
  }

  /**
   * Gets the vocabulary IDs used by products.
   *
   * @return string[]
   *   The vocabulary IDs.
   */
  protected function getVocabularyIds() {
    $vocabulary_ids = [];
    foreach ($this->getEntityReferenceFieldMap() as $field_name => $field_info) {
      foreach ($field_info['bundles'] as $bundle) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product', $bundle);
        $field_definition = $field_definitions[$field_name];
        if ($field_definition->getSetting('target_type') == 'taxonomy_term') {
          $target_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];
          if (!empty($target_bundles)) {
            $vocabulary_ids = array_merge($vocabulary_ids, $target_bundles);
          }
        }
      }
    }
    $vocabulary_ids = array_unique($vocabulary_ids);

    return $vocabulary_ids;
  }

  /**
   * Gets all referenced entity IDs for the given product.
   *
   * This includes both taxonomy term IDs, and IDs belonging to other
   * configurable entity reference fields. There is no filtering by
   * target type to avoid needlessly loading all field definitions.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return string[]
   *   The referenced entity IDs.
   */
  protected function getReferencedIds(ProductInterface $product) {
    $ids = [];
    foreach ($this->getEntityReferenceFieldMap() as $field_name => $field_info) {
      if ($product->hasField($field_name)) {
        $field = $product->get($field_name);
        if (!$field->isEmpty()) {
          foreach ($field->getValue() as $index => $field_item) {
            $ids[] = $field_item['target_id'];
          }
        }
      }
    }

    return $ids;
  }

  /**
   * Gets the field map for product entity reference fields.
   *
   * Base entity reference fields (such as stores and variations) are skipped.
   *
   * @return array
   *   The field map.
   */
  protected function getEntityReferenceFieldMap() {
    $ignore_fields = ['type', 'uid', 'stores', 'variations'];
    $ignore_fields = array_combine($ignore_fields, $ignore_fields);
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $field_map = array_diff_key($field_map['commerce_product'], $ignore_fields);

    return $field_map;
  }

}
