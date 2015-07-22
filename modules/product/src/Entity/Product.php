<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Entity\Product.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\commerce_product\ProductInterface;
use Drupal\commerce_store\StoreInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Product entity.
 *
 * @ContentEntityType(
 *   id = "commerce_product",
 *   label = @Translation("Product"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_product\ProductListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_product\Form\ProductForm",
 *       "add" = "Drupal\commerce_product\Form\ProductForm",
 *       "edit" = "Drupal\commerce_product\Form\ProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer products",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_product",
 *   data_table = "commerce_product_field_data",
 *   entity_keys = {
 *     "id" = "product_id",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/product/{commerce_product}",
 *     "edit-form" = "/product/{commerce_product}/edit",
 *     "delete-form" = "/product/{commerce_product}/delete",
 *     "collection" = "/admin/commerce/products"
 *   },
 *   bundle_entity_type = "commerce_product_type",
 *   field_ui_base_route = "entity.commerce_product_type.edit_form",
 * )
 */
class Product extends ContentEntityBase implements ProductInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

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
  public function setStoreId($storeId) {
    $this->set('store_id', $storeId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    $this->get('uid')->target_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the product variations of a deleted product.
    $variations = [];
    foreach ($entities as $entity) {
      if (empty($entity->variations)) {
        continue;
      }
      foreach ($entity->variations as $item) {
        $variations[$item->target_id] = $item->entity;
      }
    }
    $variationStorage = \Drupal::service('entity.manager')->getStorage('commerce_product_variation');
    $variationStorage->delete($variations);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    $fields['product_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Product ID'))
      ->setReadOnly(TRUE);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the product belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_product\Entity\Product::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'path',
        'weight' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setCustomStorage(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
        'settings' => [
          'display_label' => TRUE
        ]
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the product was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the product was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
