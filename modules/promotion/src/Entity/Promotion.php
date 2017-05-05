<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
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
 *   label_collection = @Translation("Promotions"),
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
 *       "default" = "Drupal\commerce_promotion\PromotionRouteProvider",
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
 *     "canonical" = "/promotion/{commerce_promotion}/edit",
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
  public function getCouponIds() {
    $coupon_ids = [];
    foreach ($this->get('coupons') as $field_item) {
      $coupon_ids[] = $field_item->target_id;
    }
    return $coupon_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoupons() {
    $coupons = $this->get('coupons')->referencedEntities();
    return $coupons;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoupons(array $coupons) {
    $this->set('coupons', $coupons);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCoupons() {
    return !$this->get('coupons')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addCoupon(CouponInterface $coupon) {
    if (!$this->hasCoupon($coupon)) {
      $this->get('coupons')->appendItem($coupon);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeCoupon(CouponInterface $coupon) {
    $index = $this->getCouponIndex($coupon);
    if ($index !== FALSE) {
      $this->get('coupons')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCoupon(CouponInterface $coupon) {
    return in_array($coupon->id(), $this->getCouponIds());
  }

  /**
   * Gets the index of the given coupon.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return int|bool
   *   The index of the given coupon, or FALSE if not found.
   */
  protected function getCouponIndex(CouponInterface $coupon) {
    return array_search($coupon->id(), $this->getCouponIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageLimit() {
    return $this->get('usage_limit')->value;
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
  public function setEndDate(DrupalDateTime $end_date = NULL) {
    $this->get('end_date')->value = $end_date ? $end_date->format('Y-m-d') : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompatibility() {
    return $this->get('compatibility')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompatibility($compatibility) {
    if (!in_array($compatibility, [self::COMPATIBLE_NONE, self::COMPATIBLE_ANY])) {
      throw new \InvalidArgumentException('Invalid compatibility type');
    }
    $this->get('compatibility')->value = $compatibility;
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
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function available(OrderInterface $order) {
    if (!$this->isEnabled()) {
      return FALSE;
    }
    if (!in_array($order->bundle(), $this->getOrderTypeIds())) {
      return FALSE;
    }
    if (!in_array($order->getStoreId(), $this->getStoreIds())) {
      return FALSE;
    }
    $time = \Drupal::time()->getRequestTime();
    if ($this->getStartDate()->format('U') > $time) {
      return FALSE;
    }
    $end_date = $this->getEndDate();
    if ($end_date && $end_date->format('U') <= $time) {
      return FALSE;
    }
    if ($usage_limit = $this->getUsageLimit()) {
      /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
      $usage = \Drupal::service('commerce_promotion.usage');
      if ($usage_limit <= $usage->getUsage($this)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    // Check compatibility.
    // @todo port remaining strategies from Commerce Discount #2762997.
    switch ($this->getCompatibility()) {
      case self::COMPATIBLE_NONE:
        // If there are any existing promotions, then this cannot apply.
        foreach ($order->collectAdjustments() as $adjustment) {
          if ($adjustment->getType() == 'promotion') {
            return FALSE;
          }
        }
        break;

      case self::COMPATIBLE_ANY:
        break;
    }

    // If there are no conditions, the promotion applies automatically.
    if ($this->get('conditions')->isEmpty()) {
      return TRUE;
    }

    $contexts = [
      'commerce_promotion' => new Context(new ContextDefinition('entity:commerce_promotion'), $this),
    ];
    // Execute each plugin, this is an AND operation.
    // @todo support OR operations.
    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $item */
    foreach ($this->get('conditions') as $item) {
      $definition = $item->getTargetDefinition();

      if ($definition['target_entity_type'] == 'commerce_order') {
        /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface $condition */
        $condition = $item->getTargetInstance($contexts + [
          'commerce_order' => new Context(new ContextDefinition('entity:commerce_order'), $order),
        ]);
        if ($condition->evaluate()) {
          return TRUE;
        }
      }
      elseif ($definition['target_entity_type'] == 'commerce_order_item') {
        foreach ($order->getItems() as $order_item) {
          /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface $condition */
          $condition = $item->getTargetInstance($contexts + [
            'commerce_order_item' => new Context(new ContextDefinition('entity:commerce_order_item'), $order_item),
          ]);
          if ($condition->evaluate()) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface $offer */
    $offer = $this->get('offer')->first()->getTargetInstance([
      'commerce_promotion' => new Context(new ContextDefinition('entity:commerce_promotion'), $this),
      'commerce_order' => new Context(new ContextDefinition('entity:commerce_order'), $order),
    ]);
    $offer->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each coupon.
    foreach ($this->coupons as $item) {
      /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
      $coupon = $item->entity;
      if (!$coupon->getPromotionId()) {
        $coupon->promotion_id = $this->id();
        $coupon->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the linked coupons and usage records.
    $coupons = [];
    foreach ($entities as $entity) {
      foreach ($entity->getCoupons() as $coupon) {
        $coupons[] = $coupon;
      }
    }
    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_promotion_coupon');
    $coupon_storage->delete($coupons);
    /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
    $usage = \Drupal::service('commerce_promotion.usage');
    $usage->deleteUsage($entities);
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
        'type' => 'commerce_plugin_radios',
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

    $fields['usage_limit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage limit'))
      ->setDescription(t('The maximum number of times the promotion can be used. 0 for unlimited.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'commerce_usage_limit',
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
        'type' => 'commerce_end_date',
        'weight' => 6,
      ]);

    $fields['compatibility'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Compatibility with other promotions'))
      ->setSetting('allowed_values_function', ['\Drupal\commerce_promotion\Entity\Promotion', 'getCompatibilityOptions'])
      ->setRequired(TRUE)
      ->setDefaultValue(self::COMPATIBLE_ANY)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 4,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether the promotion is enabled.'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'on_label' => t('Enabled'),
        'off_label' => t('Disabled'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this promotion in relation to others.'))
      ->setDefaultValue(0);

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
    $timestamp = \Drupal::time()->getRequestTime();
    return gmdate('Y-m-d', $timestamp);
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
    $timestamp = \Drupal::time()->getRequestTime();
    return gmdate('Y-m-d', $timestamp + 31536000);
  }

  /**
   * Helper callback for uasort() to sort promotions by weight and label.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $a
   *   The first promotion to sort.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $b
   *   The second promotion to sort.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sort(PromotionInterface $a, PromotionInterface $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();
    if ($a_weight == $b_weight) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Gets the allowed values for the 'compatibility' base field.
   *
   * @return array
   *   The allowed values.
   */
  public static function getCompatibilityOptions() {
    return [
      self::COMPATIBLE_ANY => t('Any promotion'),
      self::COMPATIBLE_NONE => t('Not with any other promotions'),
    ];
  }

}
