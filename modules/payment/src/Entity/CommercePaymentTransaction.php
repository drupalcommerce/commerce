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

/**
 * Defines the Commerce Transaction entity.
 *
 * @ContentEntityType(
 *   id = "commerce_payment_transaction",
 *   label = @Translation("Transaction"),
 *   controllers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce\CommercePaymentTransactionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\CommercePaymentTransactionForm",
 *       "edit" = "Drupal\commerce\CommercePaymentTransactionForm",
 *       "delete" = "Drupal\commerce\Form\CommercePaymentTransactionDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "commerce_payment_transaction",
 *   revision_table = "commerce_payment_transaction_revision",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "transaction_id",
 *     "revision" = "revision_id",
 *     "bundle" = "payment_method",
 *     "label" = "label",
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
  public function id() {
    return $this->get('transaction_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionId() {
    return $this->get('revision_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('name', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->get('currency_code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode($currency_code) {
    $this->set('currency_code', $currency_code);
    return $this;
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

    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The ID of a transaction.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = FieldDefinition::create('string')
      ->setLabel('Label')
      ->setDescription('The transation label.')
      ->setReadOnly(True)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ));

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that created this transaction.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of a transaction.'))
      ->setReadOnly(TRUE);

    $fields['order_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The ID of an order.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    $fields['instance_id'] = FieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The payment method instance ID for this transaction.'))
      ->setRequired(True)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['remote_id'] = FieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote identifier for this transaction.'))
      ->setRequired(True)
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 255,
          'text_processing' => 0,
        ))
      ->setRevisionable(TRUE);

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
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'size' => 'big',
          'text_processing' => 0,
        ));

    $fields['amount'] = FieldDefinition::create('integer')
      ->setLabel(t('Amount'))
      ->setDescription(t('The amount of this transaction.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    $fields['currency_code'] = FieldDefinition::create('string')
      ->setLabel(t('Currency code'))
      ->setDescription(t('The human-readable message associated to this transaction.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'max_length' => 32,
          'text_processing' => 0,
        ));

    $fields['status'] = FieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of this transaction (pending, success, or failure).'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['remote_status'] = FieldDefinition::create('string')
      ->setLabel(t('Remote status'))
      ->setDescription(t('The status of the transaction at the payment provider.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'max_length' => 128,
          'text_processing' => 0,
        ));

    $fields['payload'] = FieldDefinition::create('map')
      ->setLabel(t('Payload'))
      ->setDescription(t('The payment-gateway specific payload associated with this transaction.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

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
      ->setRequired(FALSE)
      ->setRevisionable(TRUE);

    // Here comes the specific fields relative to the revision table.
    $fields['revision_log'] = FieldDefinition::create('string')
      ->setLabel(t('Log'))
      ->setDescription(t('The log entry explaining the changes in this version.'))
      ->setRequired(TRUE)
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
          'default_value' => '',
          'size' => 'big',
          'text_processing' => 0,
        ));

    $fields['revision_timestamp'] = FieldDefinition::create('changed')
      ->setLabel('Revision timestamp')
      ->setDescription(t('The Unix timestamp when this revision was created.'))
      ->setRequired(TRUE)
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE)
      ->setDefaultValue(0);

    return $fields;
  }
}