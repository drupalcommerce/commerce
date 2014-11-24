<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxTypeListBuilder.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of tax types.
 */
class CommerceTaxTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['name'] = $this->t('Name');
    $header['tag'] = $this->t('Tag');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->getId();
    $row['name'] = $this->getLabel($entity);
    $row['tag'] = $entity->getTag();
    return $row + parent::buildRow($entity);
  }

}
