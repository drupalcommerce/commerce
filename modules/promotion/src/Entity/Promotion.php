<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Defines the promotion entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_promotion",
 *   label = @Translation("Promotion"),
 *   label_singular = @Translation("promotion"),
 *   label_plural = @Translation("promotions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count promotion",
 *     plural = "@count promotions",
 *   ),
 *   handlers = {
 *     "event" = "Drupal\commerce_promotion\Event\PromotionEvent",
 *     "storage" = "Drupal\commerce_promotion\PromotionStorage",
 *     "access" = "Drupal\commerce\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce\EntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_promotion\PromotionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_promotion\Form\PromotionForm",
 *       "add" = "Drupal\commerce_promotion\Form\PromotionForm",
 *       "edit" = "Drupal\commerce_promotion\Form\PromotionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_promotion",
 *   data_table = "commerce_promotion_field_data",
 *   admin_permission = "administer commerce_promotion",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "promotion_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/promotion/add",
 *     "edit-form" = "/promotion/{commerce_promotion}/edit",
 *     "delete-form" = "/promotion/{commerce_promotion}/delete",
 *     "delete-multiple-form" = "/admin/commerce/promotions/delete",
 *     "collection" = "/admin/commerce/promotions",
 *   },
 * )
 */
class Promotion extends ContentEntityBase implements PromotionInterface {

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
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
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
  public function applies(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    // @todo should whatever invokes this method be providing the context?
    $context = new Context(new ContextDefinition('entity:' . $entity_type_id), $entity);

    // Execute each plugin, this is an AND operation.
    // @todo support OR operations.
    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $item */
    foreach ($this->get('conditions') as $item) {
      /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface $condition */
      $condition = $item->getTargetInstance([$entity_type_id => $context]);
      if (!$condition->evaluate()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    // @todo should whatever invokes this method be providing the context?
    $context = new Context(new ContextDefinition('entity:' . $entity_type_id), $entity);

    /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface $offer */
    $offer = $this->get('offer')->first()->getTargetInstance([$entity_type_id => $context]);
    $offer->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The promotion name.'))
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Additional information about the promotion to show to the customer'))
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 1,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['order_types'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order types'))
      ->setDescription(t('The order types for which the promotion is valid.'))
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
      ->setDescription(t('The stores for which the promotion is valid.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 2,
      ]);

    $fields['offer'] = BaseFieldDefinition::create('commerce_plugin_item:commerce_promotion_offer')
      ->setLabel(t('Offer'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 3,
      ]);

    $fields['conditions'] = BaseFieldDefinition::create('commerce_plugin_item:commerce_promotion_condition')
      ->setLabel(t('Conditions'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 3,
      ]);

    $fields['coupons'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Coupons'))
      ->setDescription(t('Coupons which allow promotion to be redeemed.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setSetting('target_type', 'commerce_promotion_coupon')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_complex',
        'weight' => 3,
        'settings' => [
          'override_labels' => TRUE,
          'label_singular' => 'coupon',
          'label_plural' => 'coupons',
        ],
      ]);

    $fields['current_usage'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Current usage'))
      ->setDescription(t('The number of times the promotion was used.'))
      ->setDefaultValue(0);

    $fields['usage_limit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage limit'))
      ->setDescription(t('The maximum number of times the promotion can be used. 0 for unlimited.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ]);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The date the promotion becomes valid.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDefaultValueCallback('Drupal\commerce_promotion\Entity\Promotion::getDefaultStartDate')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ]);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date after which the promotion is invalid.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'commerce_optional_date',
        'weight' => 6,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Whether the promotion is enabled.'))
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
