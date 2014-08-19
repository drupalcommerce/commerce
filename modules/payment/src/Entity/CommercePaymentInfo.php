<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Entity\CommercePaymentInfo.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce_payment\CommercePaymentInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce payment information entity. It aims for storing payment
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
 *   field_ui_base_route = "entity.commerce_payment_info.admin_form",
 *   permission_granularity = "bundle",
 *   admin_permission = "administer commerce_payment_info entities",
 *   links = {
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
    $fields['information_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment information ID'))
      ->setDescription(t('Primary key: numeric payment information id.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment information owner'))
      ->setDescription(t('The payment information owner.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The payment information UUID.'))
      ->setReadOnly(TRUE);

    // Bundle.
    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method_id of the payment method that stored the payment information.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_payment_info_type');

    $fields['instance_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The instance_id of the payment method that stored the payment information.'))
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

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The id of the payment at the payment gateway.'))
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

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
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

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Payment information status: inactive (0), active (1), declined (2).'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => 1,
        'allowed_values' => array(
          0 => 'Inactive',
          1 => 'Active',
          2 => 'Declined',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 3,
        'settings' => array(
          'display_label' => TRUE
        )
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setDescription(t('The Unix timestamp when the payment information was first stored.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp when the payment information was last updated.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
