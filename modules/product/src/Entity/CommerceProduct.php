<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\CommerceProduct.
 */

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce_product\CommerceProductInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce Product entity.
 * 
 * @TODO add a data table to this definition
 *
 * @ContentEntityType(
 *   id = "commerce_product",
 *   label = @Translation("Product"),
 *   controllers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_product\CommerceProductListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product\Form\CommerceProductForm",
 *       "edit" = "Drupal\commerce_product\Form\CommerceProductForm",
 *       "delete" = "Drupal\commerce_product\Form\CommerceProductDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   admin_permission = "administer commerce_product entities",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_product",
 *   revision_table = "commerce_product_revision",
 *   revision_data_table = "commerce_product_field_revision",
 *   uri_callback = "commerce_product_uri",
 *   entity_keys = {
 *     "id" = "product_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "edit-form" = "commerce_product.edit",
 *     "delete-form" = "commerce_product.delete",
 *     "admin-form" = "commerce_product.product_type_edit"
 *   },
 *   bundle_entity_type = "commerce_product_type"
 * )
 */
class CommerceProduct extends ContentEntityBase implements CommerceProductInterface {
  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('product_id')->value;
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
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return $this->get('revision_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionId($id) {
    $this->set('revision_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Product ID
    $fields['product_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Product ID'))
      ->setDescription(t('The ID of the product.'))
      ->setReadOnly(TRUE);
    
    // Revision ID
    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The product revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    
    // uid
    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that created this product.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);
    
    // UUID
    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the product.'))
      ->setReadOnly(TRUE);

    // Language
    $fields['language'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of product.'))
      ->setRevisionable(TRUE);
    
    // Title
    $fields['title'] = FieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this node, always treated as non-markup plain text.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);
    
    // SKU
    $fields['sku'] = FieldDefinition::create('string')
      ->setLabel(t('SKU'))
      ->setDescription(t('The unique, human-readable identifier for a product.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);    
    
    // Type
    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the product.'))
      ->setRequired(TRUE);
    
    // Data
    $fields['data'] = FieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'))
      ->setRevisionable(TRUE);
    
    // Status
    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the product is active.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'options_onoff',
        'weight' => -5,
      ));
    
    // Created
    $fields['created'] = FieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the product was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Changed
    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the product was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);    

    // @todo add price field once price field type exists.

    return $fields;
  }
}
