<?php

namespace Drupal\commerce_number_pattern_test\Entity;

use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a test entity class with store.
 *
 * @ContentEntityType(
 *   id = "entity_test_with_store",
 *   label = @Translation("Test entity with store"),
 *   base_table = "entity_test_with_store",
 *   handlers = {
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_with_store/{entity_test_with_store}",
 *   },
 *   allow_number_patterns = TRUE,
 * )
 */
class EntityTestWithStore extends ContentEntityBase implements EntityStoreInterface {

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->get('store_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setStore(StoreInterface $store) {
    $this->set('store_id', $store->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the entity belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
