<?php

/**
 * @file
 * Contains \Drupal\commerce\Entity\PaymentTransaction.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Commerce Transaction entity.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_transaction",
 *   label = @Translation("Payment Transaction"),
 *   handlers = {
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
 *   admin_permission = "administer payment transactions",
 *   links = {
 *     "edit-form" = "/admin/commerce/config/payment-info-types/{commerce_payment_info_type}/edit",
 *     "delete-form" = "/admin/commerce/config/payment-info-types/{commerce_payment_info_type}/delete"
 *   },
 * )
 */
class PaymentTransaction extends ContentEntityBase implements PaymentTransactionInterface {

  /**
   * {@inheritdoc}
   */
  public function setInstanceId($instanceId) {
    $this->set('instance_id', $instanceId);
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
  public function setRemoteId($remoteId) {
    $this->set('remote_id', $remoteId);
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
  public function setRemoteStatus($remoteStatus) {
    $this->set('remote_status', $remoteStatus);
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
   * @return \Drupal\commerce_payment\Entity\PaymentTransaction
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
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    $fields['transaction_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The ID of a transaction.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that created this transaction.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of a transaction.'))
      ->setReadOnly(TRUE);

    // TODO: Use entity_reference instead of integer when the commerce_oder codebase will be merged.
    $fields['order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The ID of an order.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['instance_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The payment method instance ID for this transaction.'))
      ->setRequired(True)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ));

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote identifier for this transaction.'))
      ->setRequired(True)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ));

    // Bundle.
    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method method_id for this transaction.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 128,
      ));

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The human-readable message associated to this transaction.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'size' => 'big',
      ));

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of this transaction (pending, success, or failure).'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 128,
      ));

    $fields['remote_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote status'))
      ->setDescription(t('The status of the transaction at the payment provider.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 128,
      ));

    $fields['payload'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Payload'))
      ->setDescription(t('The payment-gateway specific payload associated with this transaction.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setDescription(t('The Unix timestamp when this transaction was created.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The Unix timestamp when this transaction was last changed.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
