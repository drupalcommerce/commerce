<?php

namespace Drupal\commerce_store\Entity;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\address\AddressInterface;
use Drupal\commerce_price\Entity\CurrencyInterface;
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
 *   label = @Translation("Store", context = "Commerce"),
 *   label_collection = @Translation("Stores", context = "Commerce"),
 *   label_singular = @Translation("store", context = "Commerce"),
 *   label_plural = @Translation("stores", context = "Commerce"),
 *   label_count = @PluralTranslation(
 *     singular = "@count store",
 *     plural = "@count stores",
 *     context = "Commerce",
 *   ),
 *   bundle_label = @Translation("Store type", context = "Commerce"),
 *   handlers = {
 *     "event" = "Drupal\commerce_store\Event\StoreEvent",
 *     "storage" = "Drupal\commerce_store\StoreStorage",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "query_access" = "Drupal\entity\QueryAccess\QueryAccessHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_store\StoreListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_store\Form\StoreForm",
 *       "add" = "Drupal\commerce_store\Form\StoreForm",
 *       "edit" = "Drupal\commerce_store\Form\StoreForm",
 *       "duplicate" = "Drupal\commerce_store\Form\StoreForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_store",
 *   data_table = "commerce_store_field_data",
 *   admin_permission = "administer commerce_store",
 *   permission_granularity = "bundle",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "store_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/store/{commerce_store}",
 *     "add-page" = "/store/add",
 *     "add-form" = "/store/add/{commerce_store_type}",
 *     "edit-form" = "/store/{commerce_store}/edit",
 *     "duplicate-form" = "/store/{commerce_store}/duplicate",
 *     "delete-form" = "/store/{commerce_store}/delete",
 *     "delete-multiple-form" = "/admin/commerce/config/stores/delete",
 *     "collection" = "/admin/commerce/config/stores",
 *   },
 *   bundle_entity_type = "commerce_store_type",
 *   field_ui_base_route = "entity.commerce_store_type.edit_form",
 * )
 */
class Store extends ContentEntityBase implements StoreInterface {

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
    return $this->getEntityKey('owner');
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
  public function getTimezone() {
    return $this->get('timezone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimezone($timezone) {
    $this->set('timezone', $timezone);
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
  public function isDefault() {
    return (bool) $this->get('is_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($is_default) {
    $this->set('is_default', (bool) $is_default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    /** @var \Drupal\commerce_store\StoreStorage $storage */
    parent::postSave($storage, $update);

    $default = $this->isDefault();
    $original_default = $this->original ? $this->original->isDefault() : FALSE;
    if ($default && !$original_default) {
      // The store was set as default, remove the flag from other stores.
      $store_ids = $storage->getQuery()
        ->condition('store_id', $this->id(), '<>')
        ->condition('is_default', TRUE)
        ->accessCheck(FALSE)
        ->execute();
      foreach ($store_ids as $store_id) {
        /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
        $store = $storage->load($store_id);
        $store->setDefault(FALSE);
        $store->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

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
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

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
      ->setDescription(t('The default currency of the store.'))
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

    $fields['timezone'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Timezone'))
      ->setDescription(t('Used when determining promotion and tax availability.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDefaultValueCallback('Drupal\commerce_store\Entity\Store::getSiteTimezone')
      ->setSetting('allowed_values_function', ['\Drupal\commerce_store\Entity\Store', 'getTimezones'])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setSetting('display_description', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('The store address.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('field_overrides', [
        AddressField::GIVEN_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDITIONAL_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::FAMILY_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ORGANIZATION => ['override' => FieldOverride::HIDDEN],
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['billing_countries'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Supported billing countries'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', ['\Drupal\commerce_store\Entity\Store', 'getAvailableCountries'])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setDescription(t('The store URL alias.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setCustomStorage(TRUE);

    // 'default' is a reserved SQL word, hence the 'is_' prefix.
    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default'))
      ->setDescription(t('Whether this is the default store.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 90,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Default value callback for the 'uid' base field definition.
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
   * Default value callback for the 'timezone' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getSiteTimezone() {
    $site_timezone = \Drupal::config('system.date')->get('timezone.default');
    if (empty($site_timezone)) {
      $site_timezone = @date_default_timezone_get();
    }

    return [$site_timezone];
  }

  /**
   * Gets the allowed values for the 'timezone' base field.
   *
   * @return array
   *   The allowed values.
   */
  public static function getTimezones() {
    return system_time_zones(NULL, TRUE);
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
