<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\CommerceStore.
 */

namespace Drupal\commerce\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce\CommerceStoreInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Commerce Store entity type.
 *
 * @ContentEntityType(
 *   id = "commerce_store",
 *   label = @Translation("Store"),
 *   bundle_label = @Translation("Store type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce\CommerceStoreListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\CommerceStoreForm",
 *       "edit" = "Drupal\commerce\Form\CommerceStoreForm",
 *       "delete" = "Drupal\commerce\Form\CommerceStoreDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_store",
 *   data_table = "commerce_store_field_data",
 *   admin_permission = "administer stores",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "store_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "entity.commerce_store.edit_form",
 *     "delete-form" = "entity.commerce_store.delete_form",
 *   },
 *   bundle_entity_type = "commerce_store_type",
 *   field_ui_base_route = "entity.commerce_store_type.edit_form",
 * )
 */
class CommerceStore extends ContentEntityBase implements CommerceStoreInterface {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId($this->getCurrentUserId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('store_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
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
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
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
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrency() {
    return $this->get('default_currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultCurrency($currency_code) {
    $this->set('default_currency', $currency_code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['store_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Store ID'))
      ->setDescription(t('The ID of the store.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the store.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store owner'))
      ->setDescription(t('The user that owns this store.'))
      ->setDefaultValueCallback('Drupal\commerce\Entity\CommerceStore::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of store.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Store name'))
      ->setDescription(t('The name of the store.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the store.'))
      ->setRequired(TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('A valid e-mail address. Store e-mail notifications will be sent to and from this address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'email',
        'weight' => 0,
      ));

    $fields['default_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default currency'))
      ->setDescription(t('The default currency of this store.'))
      ->setPropertyConstraints('value', array('length' => array('max' => 3)))
      ->setSetting('max_length', 32);

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
    return array(\Drupal::currentUser()->id());
  }

}
