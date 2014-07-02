<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\CommercePaymentTransaction.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\commerce_payment\CommercePaymentTransactionInterface;
use Drupal\Core\Entity\EntityTypeInterface;

// TODO: We need to attach a price field by default on bundles.

/**
 * Defines the Commerce Transaction entity.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_transaction",
 *   label = @Translation("Payment Transaction"),
 *   controllers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce\CommercePaymentTransactionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\CommercePaymentTransactionForm",
 *       "edit" = "Drupal\commerce\CommercePaymentTransactionForm",
 *       "delete" = "Drupal\commerce\Form\CommercePaymentTransactionDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "commerce_payment_transaction",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "bundle" = "payment_method",
 *     "uuid" = "uuid"
 *   },
 *   permission_granularity = "bundle",
 *   admin_permission = "administer commerce_payment_transaction entities",
 *   links = {
 *     "edit-form" = "commerce_payment_transaction.edit",
 *     "delete-form" = "commerce_payment_transaction.delete"
 *   },
 * )
 */
class CommercePaymentTransaction extends ContentEntityBase implements CommercePaymentTransactionInterface {
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
  public function setMessage($message) {
    $this->set('message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
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
  public function setRemoteStatus($remote_status) {
    $this->set('remote_status', $remote_status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteStatus() {
    return $this->get('remote_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPayload($payload) {
    $this->set('payload', $payload);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPayload() {
    return $this->get('payload')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * Sets the Unix timestamp when this transaction was created.
   *
   * @param array $created
   *   An Unix timestamp.
   *
   * @return \Drupal\commerce_payment\Entity\CommercePaymentTransaction
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
    $fields['transaction_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The ID of a transaction.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that created this transaction.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of a transaction.'))
      ->setReadOnly(TRUE);

    // TODO: Use entity_reference instead of integer when the commerce_oder codebase will be merged.
    $fields['order_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The ID of an order.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['instance_id'] = FieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The payment method instance ID for this transaction.'))
      ->setRequired(True)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['remote_id'] = FieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote identifier for this transaction.'))
      ->setRequired(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    // Bundle.
    $fields['payment_method'] = FieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method method_id for this transaction.'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['message'] = FieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The human-readable message associated to this transaction.'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'size' => 'big',
          'text_processing' => 0,
        ));

    $fields['status'] = FieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of this transaction (pending, success, or failure).'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['remote_status'] = FieldDefinition::create('string')
      ->setLabel(t('Remote status'))
      ->setDescription(t('The status of the transaction at the payment provider.'))
      ->setRequired(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['payload'] = FieldDefinition::create('map')
      ->setLabel(t('Payload'))
      ->setDescription(t('The payment-gateway specific payload associated with this transaction.'))
      ->setRequired(TRUE);

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel('Created')
      ->setDescription(t('The Unix timestamp when this transaction was created.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp when this transaction was last changed.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['data'] = FieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'))
      ->setRequired(FALSE);

    return $fields;
  }
}
