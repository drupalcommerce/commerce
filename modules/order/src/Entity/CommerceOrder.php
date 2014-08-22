<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Entity\CommerceOrder.
 */

namespace Drupal\commerce_order\Entity;

use Drupal\commerce_order\CommerceOrderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Commerce Order entity.
 *
 * @ContentEntityType(
 *   id = "commerce_order",
 *   label = @Translation("Order"),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_order\CommerceOrderListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\CommerceOrderForm",
 *       "edit" = "Drupal\commerce_order\Form\CommerceOrderForm",
 *       "delete" = "Drupal\commerce_order\Form\CommerceOrderDeleteForm"
 *     }
 *   },
 *   base_table = "commerce_order",
 *   admin_permission = "administer commerce_order entities",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "order_id",
 *     "label" = "order_number",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "edit-form" = "entity.commerce_order.edit_form",
 *     "delete-form" = "entity.commerce_order.delete_form"
 *   },
 *   bundle_entity_type = "commerce_order_type",
 *   field_ui_base_route = "entity.commerce_order.admin_form",
 * )
 */
class CommerceOrder extends ContentEntityBase implements CommerceOrderInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }

    // If no revision author has been set explicitly, make the order owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }

    if ($this->isNew()) {
      if (!$this->getHostname()) {
        $this->setHostname(\Drupal::request()->getClientIp());
      }

      if (!$this->getEmail()) {
        $this->setEmail($this->getOwner()->getEmail());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing order without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If no order number has been set explicitly, set it to the order id.
    if (!$this->getOrderNumber()) {
      $this->setOrderNumber($this->id());
      $this->save();
    }
  }

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
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
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
  public function getChangedTime() {
    return $this->get('changed')->value;
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
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItems() {
    return $this->get('line_items')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItems($line_items) {
    $this->set('line_items', array($line_items));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->get('hostname')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostname($hostname) {
    $this->set('hostname', $hostname);
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
  public function getData() {
    return $this->get('data')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', array($data));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The order ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number'))
      ->setDescription(t('The order number displayed to the customer.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The order UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The order revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The order type.'))
      ->setSetting('target_type', 'commerce_order_type')
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that owns this order.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ));

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The e-mail address associated with the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status name of this order.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the order was created.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the order was last edited.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['line_items'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Line items'))
      ->setDescription(t('A serialized array of line items.'));

    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hostname'))
      ->setDescription(t('The IP address that created this order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

}
