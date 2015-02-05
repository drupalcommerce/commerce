<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatListBuilder.
 */

namespace Drupal\commerce_price;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Order types.
 */
class NumberFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->getLabel($entity);
    $row['status'] = $entity->status() ? t('Enabled') : t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
