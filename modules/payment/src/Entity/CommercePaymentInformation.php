<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\CommercePaymentTransaction.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce_payment\CommercePaymentInformationInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce Transaction entity. It aims for storing card's
 * information and payment tokens.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_information",
 *   label = @Translation("Payment Information"),
 *   controllers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce\CommercePaymentInformationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\CommercePaymentInformationForm",
 *       "edit" = "Drupal\commerce\CommercePaymentInformationForm",
 *       "delete" = "Drupal\commerce\Form\CommercePaymentInformationDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_payment_information",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "card_id",
 *     "uuid" = "uuid"
 *   },
 *   admin_permission = "administer commerce_payment_information entities",
 *   links = {
 *     "edit-form" = "commerce_payment_information.edit",
 *     "delete-form" = "commerce_payment_information.delete"
 *   },
 * )
 */
class CommercePaymentInformation extends ContentEntityBase implements CommercePaymentInformationInterface {
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
  public function setCardType($card_type) {
    $this->set('card_type', $card_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardType() {
    return $this->get('card_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardName($card_name) {
    $this->set('card_name', $card_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardName() {
    return $this->get('card_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardNumber($card_number) {
    $this->set('card_number', $card_number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardNumber() {
    $this->get('card_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardExpMonth($card_exp_month) {
    $this->set('card_exp_month', $card_exp_month);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardExpMonth() {
    return $this->get('card_exp_month')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardExpYear($card_exp_year) {
    $this->set('card_exp_year', $card_exp_year);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardExpYear() {
    return $this->get('card_exp_year')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceDefault($instance_default) {
    $this->set('instance_default', $instance_default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceDefault() {
    return $this->get('instance_default')->value;
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
   * @return \Drupal\commerce_payment\Entity\CommercePaymentInformation
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
    $fields['card_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Card ID'))
      ->setDescription(t('Primary key: numeric card id.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Card owner'))
      ->setDescription(t('The card owner.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The card owner UUID.'))
      ->setReadOnly(TRUE);

    // Bundle.
    $fields['payment_method'] = FieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method_id of the payment method that stored the card.'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['instance_id'] = FieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The instance_id of the payment method that stored the card.'))
      ->setRequired(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    $fields['remote_id'] = FieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The id of the card at the payment gateway.'))
      ->setRequired(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    $fields['card_type'] = FieldDefinition::create('string')
      ->setLabel(t('Card Type'))
      ->setDescription(t('The card type.'))
      ->setRequired(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    $fields['card_name'] = FieldDefinition::create('string')
      ->setLabel(t('Card Name'))
      ->setDescription(t('The card name.'))
      ->setRequired(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    $fields['card_number'] = FieldDefinition::create('string')
      ->setLabel(t('Card Number'))
      ->setDescription(t('Truncated card number (last 4 digits).'))
      ->setRequired(True)
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
        ));

    $fields['instance_default'] = FieldDefinition::create('integer')
      ->setLabel(t('Instance Default'))
      ->setDescription(t('Whether this is the default card for this payment method instance.'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'size' => 'tiny',
          'default_value' => 0,
        ));

    $fields['status'] = FieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Card status: inactive (0), active (1), not deletable (2), declined (3).'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => 0,
          'size' => 'tiny',
        ));

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