<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for payment methods.
 */
class PaymentMethodListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'methods';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_methods';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Payment method');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $entity */
    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

}