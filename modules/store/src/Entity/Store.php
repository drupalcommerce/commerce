<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\Store.
 */

namespace Drupal\commerce_store\Entity;

use CommerceGuys\Addressing\Enum\AddressField;
use Drupal\address\AddressInterface;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\entity\EntityKeysFieldsTrait;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the store entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_store",
 *   label = @Translation("Store"),
 *   bundle_label = @Translation("Store type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_store\Event\StoreEvent",
 *     "storage" = "Drupal\commerce_store\StoreStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_store\StoreListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_store\Form\StoreForm",
 *       "edit" = "Drupal\commerce_store\Form\StoreForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "create" = "Drupal\entity\Routing\AdminCreateHtmlRouteProvider",
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
 *     "add-page" = "/store/add",
 *     "add-form" = "/store/add/{type}",
 *     "edit-form" = "/store/{commerce_store}/edit",
 *     "delete-form" = "/store/{commerce_store}/delete",
 *     "collection" = "/admin/commerce/stores",
 *   },
 *   bundle_entity_type = "commerce_store_type",
 *   field_ui_base_route = "entity.commerce_store_type.edit_form",
 * )
 */
class Store extends ContentEntityBase implements StoreInterface {

  use EntityKeysFieldsTrait;

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
  public function setDefaultCurrencyCode($currency_code) {
    $this->set('default_currency', $currency_code);
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
  public function getBillingCountries() {
    $countries = [];
    foreach ($this->get('billing_countries') as $countryItem) {
      $countries[] = $countryItem->value;
    }
    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingCountries(array $countries) {
    $this->set('billing_countries', $countries);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = self::entityKeysBaseFieldDefinitions($entity_type);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The store type.'))
      ->setSetting('target_type', 'commerce_store_type')
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The store owner.'))
      ->setDefaultValueCallback('Drupal\commerce_store\Entity\Store::getCurrentUserId')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 50,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The store name.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Store email notifications are sent from this address.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 1,
      ])
      ->setSetting('display_description', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['default_currency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Default currency'))
      ->setDescription('The default currency of the store.')
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
    $disabled_fields = [AddressField::RECIPIENT, AddressField::ORGANIZATION];
    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The store address.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('fields', array_diff(AddressField::getAll(), $disabled_fields))
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'settings' => [],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['billing_countries'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Supported billing countries'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', ['\Drupal\commerce_store\Entity\Store', 'getAvailableCountries'])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
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

  /**
   * Gets the allowed values for the 'billing_countries' base field.
   *
   * @return array
   *   The allowed values.
   */
  public static function getAvailableCountries() {
    return \Drupal::service('address.country_repository')->getList();
  }

}
