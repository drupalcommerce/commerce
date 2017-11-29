<?php

namespace Drupal\commerce_order\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the order entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_order",
 *   label = @Translation("Order"),
 *   label_collection = @Translation("Orders"),
 *   label_singular = @Translation("order"),
 *   label_plural = @Translation("orders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order",
 *     plural = "@count orders",
 *   ),
 *   bundle_label = @Translation("Order type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_order\Event\OrderEvent",
 *     "storage" = "Drupal\commerce_order\OrderStorage",
 *     "access" = "Drupal\commerce_order\OrderAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_order\OrderPermissionProvider",
 *     "list_builder" = "Drupal\commerce_order\OrderListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_order\Form\OrderForm",
 *       "add" = "Drupal\commerce_order\Form\OrderForm",
 *       "edit" = "Drupal\commerce_order\Form\OrderForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "unlock" = "Drupal\commerce_order\Form\OrderUnlockForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_order\OrderRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_order",
 *   admin_permission = "administer commerce_order",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "order_id",
 *     "label" = "order_number",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/orders/{commerce_order}",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/delete",
 *     "delete-multiple-form" = "/admin/commerce/orders/delete",
 *     "reassign-form" = "/admin/commerce/orders/{commerce_order}/reassign",
 *     "unlock-form" = "/admin/commerce/orders/{commerce_order}/unlock",
 *     "collection" = "/admin/commerce/orders"
 *   },
 *   bundle_entity_type = "commerce_order_type",
 *   field_ui_base_route = "entity.commerce_order_type.edit_form"
 * )
 */
class Order extends CommerceContentEntityBase implements OrderInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getOrderNumber() {
    return $this->get('order_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderNumber($order_number) {
    $this->set('order_number', $order_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->getTranslatedReferencedEntity('store_id');
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
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerId($uid) {
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
  public function getIpAddress() {
    return $this->get('ip_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIpAddress($ip_address) {
    $this->set('ip_address', $ip_address);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfile() {
    return $this->get('billing_profile')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingProfile(ProfileInterface $profile) {
    $this->set('billing_profile', $profile);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('order_items')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $order_items) {
    $this->set('order_items', $order_items);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItems() {
    return !$this->get('order_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(OrderItemInterface $order_item) {
    if (!$this->hasItem($order_item)) {
      $this->get('order_items')->appendItem($order_item);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(OrderItemInterface $order_item) {
    $index = $this->getItemIndex($order_item);
    if ($index !== FALSE) {
      $this->get('order_items')->offsetUnset($index);
      $this->recalculateTotalPrice();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem(OrderItemInterface $order_item) {
    return $this->getItemIndex($order_item) !== FALSE;
  }

  /**
   * Gets the index of the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return int|bool
   *   The index of the given order item, or FALSE if not found.
   */
  protected function getItemIndex(OrderItemInterface $order_item) {
    $values = $this->get('order_items')->getValue();
    $order_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($order_item->id(), $order_item_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustments() {
    return $this->get('adjustments')->getAdjustments();
  }

  /**
   * {@inheritdoc}
   */
  public function setAdjustments(array $adjustments) {
    $this->set('adjustments', $adjustments);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->appendItem($adjustment);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->removeAdjustment($adjustment);
    $this->recalculateTotalPrice();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearAdjustments() {
    $locked_callback = function ($adjustment) {
      /** @var \Drupal\commerce_order\Adjustment $adjustment */
      return $adjustment->isLocked();
    };
    // Remove all unlocked adjustments.
    foreach ($this->getItems() as $order_item) {
      $adjustments = array_filter($order_item->getAdjustments(), $locked_callback);
      $order_item->setAdjustments($adjustments);
    }
    $adjustments = array_filter($this->getAdjustments(), $locked_callback);
    $this->setAdjustments($adjustments);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function collectAdjustments() {
    $adjustments = [];
    foreach ($this->getItems() as $order_item) {
      foreach ($order_item->getAdjustments() as $adjustment) {
        // Order item adjustments apply to the unit price, they
        // must be multiplied by quantity before they are used.
        $multiplied_adjustment = new Adjustment([
          'type' => $adjustment->getType(),
          'label' => $adjustment->getLabel(),
          'amount' => $adjustment->getAmount()->multiply($order_item->getQuantity()),
          'percentage' => $adjustment->getPercentage(),
          'source_id' => $adjustment->getSourceId(),
          'included' => $adjustment->isIncluded(),
          'locked' => $adjustment->isLocked(),
        ]);
        $adjustments[] = $multiplied_adjustment;
      }
    }
    foreach ($this->getAdjustments() as $adjustment) {
      $adjustments[] = $adjustment;
    }

    return $adjustments;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotalPrice() {
    /** @var \Drupal\commerce_price\Price $subtotal_price */
    $subtotal_price = NULL;
    if ($this->hasItems()) {
      foreach ($this->getItems() as $order_item) {
        $order_item_total = $order_item->getTotalPrice();
        $subtotal_price = $subtotal_price ? $subtotal_price->add($order_item_total) : $order_item_total;
      }
    }
    return $subtotal_price;
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateTotalPrice() {
    /** @var \Drupal\commerce_price\Price $total_price */
    $total_price = NULL;
    if ($this->hasItems()) {
      foreach ($this->getItems() as $order_item) {
        $order_item_total = $order_item->getTotalPrice();
        $total_price = $total_price ? $total_price->add($order_item_total) : $order_item_total;
      }
      foreach ($this->collectAdjustments() as $adjustment) {
        if (!$adjustment->isIncluded()) {
          $total_price = $total_price->add($adjustment->getAmount());
        }
      }
    }
    $this->total_price = $total_price;

    return $this;
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
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshState() {
    return $this->getData('refresh_state');
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshState($refresh_state) {
    return $this->setData('refresh_state', $refresh_state);
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
  public function isLocked() {
    return !empty($this->get('locked')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function lock() {
    $this->set('locked', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unlock() {
    $this->set('locked', FALSE);
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
  public function getPlacedTime() {
    return $this->get('placed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlacedTime($timestamp) {
    $this->set('placed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTime($timestamp) {
    $this->set('completed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->isNew()) {
      if (!$this->getIpAddress()) {
        $this->setIpAddress(\Drupal::request()->getClientIp());
      }
    }

    if (!$this->getEmail() && $customer = $this->getCustomer()) {
      $this->setEmail($customer->getEmail());
    }

    // Maintain the completed timestamp.
    $state = $this->getState()->value;
    $original_state = isset($this->original) ? $this->original->getState()->value : '';
    if ($state == 'completed' && $original_state != 'completed') {
      if (empty($this->getCompletedTime())) {
        $this->setCompletedTime(\Drupal::time()->getRequestTime());
      }
    }
    // Refresh draft orders on every save.
    if ($this->getState()->value == 'draft' && empty($this->getRefreshState())) {
      $this->setRefreshState(self::REFRESH_ON_SAVE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each order item.
    foreach ($this->getItems() as $order_item) {
      if ($order_item->order_id->isEmpty()) {
        $order_item->order_id = $this->id();
        $order_item->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the order items of a deleted order.
    $order_items = [];
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    foreach ($entities as $entity) {
      foreach ($entity->getItems() as $order_item) {
        $order_items[$order_item->id()] = $order_item;
      }
    }
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_order_item');
    $order_item_storage->delete($order_items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number'))
      ->setDescription(t('The order number displayed to the customer.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the order belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The customer.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_order\Entity\Order::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address of the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Billing information'))
      ->setDescription(t('Billing profile'))
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['customer']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_billing_profile',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'commerce_adjustment_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the order.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'commerce_order_total_summary',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The order state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_order\Entity\Order', 'getWorkflowId']);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setSettings([
        'on_label' => t('Yes'),
        'off_label' => t('No'),
      ])
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the order was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the order was last edited.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['placed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Placed'))
      ->setDescription(t('The time when the order was placed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time when the order was completed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

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
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(OrderInterface $order) {
    $workflow = OrderType::load($order->bundle())->getWorkflowId();
    return $workflow;
  }

}
