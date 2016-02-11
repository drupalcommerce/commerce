<?php

namespace Drupal\commerce_price;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for currencies.
 */
class CurrencyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'name' => $this->t('Name'),
      'currencyCode' => $this->t('Currency code'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'name' => $entity->label(),
      'currencyCode' => $entity->id(),
    ];

    return $row + parent::buildRow($entity);
  }

}
