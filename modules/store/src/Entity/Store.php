<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\Store.
 */

namespace Drupal\commerce_store\Entity;

use CommerceGuys\Addressing\Enum\AddressField;
use Drupal\address\AddressInterface;
use Drupal\commerce_price\CurrencyInterface;
use Drupal\commerce_store\StoreInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce Store entity type.
 *
 * @ContentEntityType(
 *   id = "commerce_store",
 *   label = @Translation("Store"),
 *   bundle_label = @Translation("Store type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_store\StoreStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_store\StoreListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_store\Form\StoreForm",
 *       "edit" = "Drupal\commerce_store\Form\StoreForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
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
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/store/{commerce_store}",
 *     "edit-form" = "/store/{commerce_store}/edit",
 *     "delete-form" = "/store/{commerce_store}/delete",
 *     "collection" = "/admin/commerce/stores",
 *   },
 *   bundle_entity_type = "commerce_store_type",
 *   field_ui_base_route = "entity.commerce_store_type.edit_form",
 * )
 */
class Store extends ContentEntityBase implements StoreInterface {

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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
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
    return $this->get('default_currency')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultCurrency(CurrencyInterface $currency) {
    $this->set('default_currency', $currency->getCurrencyCode());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCurrencyCode() {
    return $this->get('default_currency')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultCurrencyCode($currencyCode) {
    $this->set('default_currency', $currencyCode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {
    return $this->get('address')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress(AddressInterface $address) {
    // $this->set('address', $address) results in the address being appended
    // to the item list, instead of replacing the existing first item.
    $this->address = $address;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountries() {
    $countries = [];
    foreach ($this->get('countries') as $countryItem) {
      $countries[] = $countryItem->value;
    }
    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountries(array $countries) {
    $this->set('countries', $countries);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    $fields['store_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Store ID'))
      ->setDescription(t('The ID of the store.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the store.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDefaultValueCallback('Drupal\commerce_store\Entity\Store::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 50,
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of store.'))
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => -1,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the store.'))
      ->setRequired(TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('Store e-mail notifications will be sent to and from this address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['default_currency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Default currency'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_currency')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Disable the recipient and organization fields on the store address.
    $disabledFields = [AddressField::RECIPIENT, AddressField::ORGANIZATION];
    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('fields', array_diff(AddressField::getAll(), $disabledFields))
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'settings' => [],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['countries'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Countries'))
      ->setDescription(t('The countries this store sells to.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'commerce_store_available_countries')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

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
