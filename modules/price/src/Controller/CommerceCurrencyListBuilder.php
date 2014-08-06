<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\Controller\CommerceCurrencyListBuilder.
 */

namespace Drupal\commerce_price\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Order types.
 */
class CommerceCurrencyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['currencyCode'] = $this->t('Currency code');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->getLabel($entity);
    $row['currencyCode'] = $entity->id();
    $row['status'] = $entity->status() ? t('Enabled') : t('Disabled');
    return $row + parent::buildRow($entity);
  }
}
