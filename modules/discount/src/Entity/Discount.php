<?php

namespace Drupal\commerce_discount\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the discount entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_discount",
 *   label = @Translation("Discount"),
 *   label_singular = @Translation("Discount"),
 *   label_plural = @Translation("Discounts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count discount",
 *     plural = "@count discounts",
 *   ),
 *   handlers = {
 *     "event" = "Drupal\commerce_discount\Event\DiscountEvent",
 *     "storage" = "Drupal\commerce_discount\DiscountStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_discount\DiscountListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_discount",
 *   data_table = "commerce_discount_field_data",
 *   admin_permission = "administer discounts",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "discount_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/discount/add",
 *     "edit-form" = "/discount/{commerce_discount}/edit",
 *     "delete-form" = "/discount/{commerce_discount}/delete",
 *     "delete-multiple-form" = "/admin/commerce/discounts/delete",
 *     "collection" = "/admin/commerce/discounts",
 *   },
 * )
 */
class Discount extends ContentEntityBase implements DiscountInterface {

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
  public function getOrderTypes() {
    return $this->get('order_types')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderTypes(array $order_types) {
    $this->set('order_types', $order_types);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderTypeIds() {
    $order_type_ids = [];
    foreach ($this->get('order_types') as $field_item) {
      $order_type_ids[] = $field_item->target_id;
    }
    return $order_type_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderTypeIds(array $order_type_ids) {
    $this->set('order_types', $order_type_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->get('stores')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->get('stores') as $field_item) {
      $store_ids[] = $field_item->target_id;
    }
    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $store_ids) {
    $this->set('stores', $store_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUsage() {
    return $this->get('current_usage')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUsage($current_usage) {
    $this->set('current_usage', $current_usage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageLimit() {
    return $this->get('usage_limit')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsageLimit($usage_limit) {
    $this->set('usage_limit', $usage_limit);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->get('start_date')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(DrupalDateTime $start_date) {
    $this->get('start_date')->value = $start_date->format('Y-m-d');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->get('end_date')->date;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(DrupalDateTime $end_date) {
    $this->get('end_date')->value = $end_date->format('Y-m-d');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->set('status', (bool) $enabled);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The discount name.'))
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

    $fields['order_types'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order types'))
      ->setDescription(t('The order types for which the discount is valid.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_order_type')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 2,
      ]);

    $fields['stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stores'))
      ->setDescription(t('The stores for which the discount is valid.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 2,
      ]);

    $fields['current_usage'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Current usage'))
      ->setDescription(t('The number of times the discount was used.'))
      ->setDefaultValue(0);

    $fields['usage_limit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage limit'))
      ->setDescription(t('The maximum number of times the discount can be used. 0 for unlimited.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ]);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The date the discount becomes valid.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDefaultValueCallback('Drupal\commerce_discount\Entity\Discount::getDefaultStartDate')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
      ]);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date after which the discount is invalid.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDefaultValueCallback('Drupal\commerce_discount\Entity\Discount::getDefaultEndDate')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Whether the discount is enabled.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 20,
      ]);

    return $fields;
  }

  /**
   * Default value callback for 'start_date' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return string
   *   The default value (date string).
   */
  public static function getDefaultStartDate() {
    return gmdate('Y-m-d');
  }

  /**
   * Default value callback for 'end_date' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return int
   *   The default value (date string).
   */
  public static function getDefaultEndDate() {
    // Today + 1 year.
    return gmdate('Y-m-d', time() + 31536000);
  }

}
