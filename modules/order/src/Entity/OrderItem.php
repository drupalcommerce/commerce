<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the order item entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_order_item",
 *   label = @Translation("Order item"),
 *   label_singular = @Translation("order item"),
 *   label_plural = @Translation("order items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order item",
 *     plural = "@count order items",
 *   ),
 *   bundle_label = @Translation("Order item type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_order\Event\OrderItemEvent",
 *     "storage" = "Drupal\commerce_order\OrderItemStorage",
 *     "access" = "Drupal\commerce_order\OrderItemAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_order\OrderItemPermissionProvider",
 *     "views_data" = "Drupal\commerce_order\OrderItemViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "inline_form" = "Drupal\commerce_order\Form\OrderItemInlineForm",
 *   },
 *   base_table = "commerce_order_item",
 *   admin_permission = "administer commerce_order",
 *   entity_keys = {
 *     "id" = "order_item_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "title",
 *   },
 *   bundle_entity_type = "commerce_order_item_type",
 *   field_ui_base_route = "entity.commerce_order_item_type.edit_form",
 * )
 */
class OrderItem extends CommerceContentEntityBase implements OrderItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPurchasedEntity() {
    return !$this->get('purchased_entity')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    return $this->getTranslatedReferencedEntity('purchased_entity');
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntityId() {
    return $this->get('purchased_entity')->target_id;
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
  public function getQuantity() {
    return (string) $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', (string) $quantity);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnitPrice() {
    if (!$this->get('unit_price')->isEmpty()) {
      return $this->get('unit_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUnitPrice(Price $unit_price, $override = FALSE) {
    $this->set('unit_price', $unit_price);
    $this->set('overridden_unit_price', $override);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnitPriceOverridden() {
    return (bool) $this->get('overridden_unit_price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    if (!$this->get('total_price')->isEmpty()) {
      return $this->get('total_price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustments(array $adjustment_types = []) {
    /** @var \Drupal\commerce_order\Adjustment[] $adjustments */
    $adjustments = $this->get('adjustments')->getAdjustments();
    // Filter adjustments by type, if needed.
    if ($adjustment_types) {
      foreach ($adjustments as $index => $adjustment) {
        if (!in_array($adjustment->getType(), $adjustment_types)) {
          unset($adjustments[$index]);
        }
      }
      $adjustments = array_values($adjustments);
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdjustments(array $adjustments) {
    $this->set('adjustments', $adjustments);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->appendItem($adjustment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->removeAdjustment($adjustment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function usesLegacyAdjustments() {
    return (bool) $this->get('uses_legacy_adjustments')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedTotalPrice(array $adjustment_types = []) {
    $total_price = $this->getTotalPrice();
    if (!$total_price) {
      return NULL;
    }

    if ($this->usesLegacyAdjustments()) {
      $adjusted_unit_price = $this->getAdjustedUnitPrice($adjustment_types);
      $adjusted_total_price = $adjusted_unit_price->multiply($this->getQuantity());
    }
    else {
      $adjusted_total_price = $this->applyAdjustments($total_price, $adjustment_types);
    }

    $rounder = \Drupal::service('commerce_price.rounder');
    $adjusted_total_price = $rounder->round($adjusted_total_price);

    return $adjusted_total_price;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedUnitPrice(array $adjustment_types = []) {
    $unit_price = $this->getUnitPrice();
    if (!$unit_price) {
      return NULL;
    }

    if ($this->usesLegacyAdjustments()) {
      $adjusted_unit_price = $this->applyAdjustments($unit_price, $adjustment_types);
    }
    else {
      $adjusted_total_price = $this->getAdjustedTotalPrice($adjustment_types);
      $adjusted_unit_price = $adjusted_total_price->divide($this->getQuantity());
    }

    $rounder = \Drupal::service('commerce_price.rounder');
    $adjusted_unit_price = $rounder->round($adjusted_unit_price);

    return $adjusted_unit_price;
  }

  /**
   * Applies adjustments to the given price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   * @param string[] $adjustment_types
   *   The adjustment types to include in the adjusted price.
   *   Examples: fee, promotion, tax. Defaults to all adjustment types.
   *
   * @return \Drupal\commerce_price\Price
   *   The adjusted price.
   */
  protected function applyAdjustments(Price $price, array $adjustment_types = []) {
    $adjusted_price = $price;
    foreach ($this->getAdjustments($adjustment_types) as $adjustment) {
      if (!$adjustment->isIncluded()) {
        $adjusted_price = $adjusted_price->add($adjustment->getAmount());
      }
    }
    return $adjusted_price;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetData($key) {
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
      unset($data[$key]);
      $this->set('data', $data);
    }
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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->recalculateTotalPrice();
  }

  /**
   * Recalculates the order item total price.
   */
  protected function recalculateTotalPrice() {
    if ($unit_price = $this->getUnitPrice()) {
      $rounder = \Drupal::service('commerce_price.rounder');
      $total_price = $unit_price->multiply($this->getQuantity());
      $this->total_price = $rounder->round($total_price);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The order backreference, populated by Order::postSave().
    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setSetting('target_type', 'commerce_order')
      ->setReadOnly(TRUE);

    $fields['purchased_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchased entity'))
      ->setDescription(t('The purchased entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The order item title.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 512,
      ]);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of purchased units.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'commerce_quantity',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Unit price'))
      ->setDescription(t('The price of a single unit.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_unit_price',
        'weight' => 2,
        'settings' => [
          'require_confirmation' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['overridden_unit_price'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Overridden unit price'))
      ->setDescription(t('Whether the unit price is overridden.'))
      ->setDefaultValue(FALSE);

    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the order item.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uses_legacy_adjustments'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Uses legacy adjustments'))
      ->setSettings([
        'on_label' => t('Yes'),
        'off_label' => t('No'),
      ])
      ->setDefaultValue(FALSE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the order item was created.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the order item was last edited.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type = OrderItemType::load($bundle);
    if (!$order_item_type) {
      throw new \RuntimeException(sprintf('Could not load the "%s" order item type.', $bundle));
    }
    $purchasable_entity_type = $order_item_type->getPurchasableEntityTypeId();

    $fields = [];
    $fields['purchased_entity'] = clone $base_field_definitions['purchased_entity'];
    if ($purchasable_entity_type) {
      $fields['purchased_entity']->setSetting('target_type', $purchasable_entity_type);
    }
    else {
      // This order item type won't reference a purchasable entity. The field
      // can't be removed here, or converted to a configurable one, so it's
      // hidden instead. https://www.drupal.org/node/2346347#comment-10254087.
      $fields['purchased_entity']->setRequired(FALSE);
      $fields['purchased_entity']->setDisplayOptions('form', [
        'region' => 'hidden',
      ]);
      $fields['purchased_entity']->setDisplayConfigurable('form', FALSE);
      $fields['purchased_entity']->setDisplayConfigurable('view', FALSE);
      $fields['purchased_entity']->setReadOnly(TRUE);

      // Make the title field visible and required.
      $fields['title'] = clone $base_field_definitions['title'];
      $fields['title']->setRequired(TRUE);
      $fields['title']->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ]);
      $fields['title']->setDisplayConfigurable('form', TRUE);
      $fields['title']->setDisplayConfigurable('view', TRUE);

      // The unit price is always an override when there's no purchased entity.
      $fields['unit_price'] = clone $base_field_definitions['unit_price'];
      $fields['unit_price']->setDisplayOptions('form', [
        'type' => 'commerce_unit_price',
        'weight' => 2,
        'settings' => [
          'require_confirmation' => FALSE,
        ],
      ]);
    }

    return $fields;
  }

}
