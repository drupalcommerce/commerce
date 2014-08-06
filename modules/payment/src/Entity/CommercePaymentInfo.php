<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Entity\CommercePaymentInfo.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce_payment\CommercePaymentInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce payment information entity. It aims for storing card's
 * information and payment tokens.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_info",
 *   label = @Translation("Payment information"),
 *   bundle_label = @Translation("Payment information type"),
 *   controllers = {
 *     "list_builder" = "Drupal\commerce_payment\CommercePaymentInfoListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_payment\Form\CommercePaymentInfoForm",
 *       "edit" = "Drupal\commerce_payment\Form\CommercePaymentInfoForm",
 *       "delete" = "Drupal\commerce_payment\Form\CommercePaymentInfoDeleteForm"
 *     }
 *   },
 *   base_table = "commerce_payment_info",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "information_id",
 *     "bundle" = "payment_method",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "commerce_payment_info_type",
 *   permission_granularity = "bundle",
 *   admin_permission = "administer commerce_payment_info entities",
 *   links = {
 *     "admin-form" = "entity.commerce_payment_info.admin_form",
 *     "edit-form" = "entity.commerce_payment_info.edit_form",
 *     "delete-form" = "entity.commerce_payment_info.delete_form"
 *   },
 * )
 */
class CommercePaymentInfo extends ContentEntityBase implements CommercePaymentInfoInterface {
  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod($method_id) {
    $this->set('payment_method', $method_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceId($instance_id) {
    $this->set('instance_id', $instance_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceId() {
    return $this->get('instance_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->set('is_default', $default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault() {
    return $this->get('is_default')->value;
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * Sets the Unix timestamp when this payment was created.
   *
   * @param array $created
   *   An Unix timestamp.
   *
   * @return \Drupal\commerce_payment\Entity\CommercePaymentInfo
   *   The class instance that this method is called on.
   */
  protected function setCreated($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setChanged($changed) {
    $this->set('changed', $changed);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['information_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Payment information ID'))
      ->setDescription(t('Primary key: numeric payment information id.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Payment information owner'))
      ->setDescription(t('The payment information owner.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ));

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The card owner UUID.'))
      ->setReadOnly(TRUE);

    // Bundle.
    $fields['payment_method'] = FieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method_id of the payment method that stored the card.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_payment_info_type');

    $fields['instance_id'] = FieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The instance_id of the payment method that stored the card.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['remote_id'] = FieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The id of the card at the payment gateway.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE);

    /*$fields['card_type'] = FieldDefinition::create('string')
      ->setLabel(t('Card Type'))
      ->setDescription(t('The card type.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['card_name'] = FieldDefinition::create('string')
      ->setLabel(t('Card Name'))
      ->setDescription(t('The card name.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['card_number'] = FieldDefinition::create('string')
      ->setLabel(t('Card Number'))
      ->setDescription(t('Truncated card number (last 4 digits).'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 4,
        'text_processing' => 0,
      ));

    $fields['card_exp_month'] = FieldDefinition::create('integer')
      ->setLabel(t('Card Expiration Month'))
      ->setDescription(t('The card expiration month.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'size' => 'tiny',
        'default_value' => 0,
      ));

    $fields['card_exp_year'] = FieldDefinition::create('integer')
      ->setLabel(t('Card Expiration Year'))
      ->setDescription(t('The card expiration year.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'size' => 'tiny',
        'default_value' => 0,
      ));*/

    $fields['is_default'] = FieldDefinition::create('boolean')
      ->setLabel(t('Default'))
      ->setDescription(t('Whether this is the default element for this payment method instance.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 3,
        'settings' => array(
          'display_label' => TRUE
        )
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = FieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Card status: inactive (0), active (1), not deletable (2), declined (3).'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 3,
        'settings' => array(
          'display_label' => TRUE
        )
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel('Created')
      ->setDescription(t('The Unix timestamp when the card was first stored.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp when the card was last updated.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['data'] = FieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'))
      ->setRequired(FALSE);

    return $fields;
  }
}
