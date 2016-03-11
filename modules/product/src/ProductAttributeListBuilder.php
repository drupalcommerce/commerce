<?php

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for product attributes.
 */
class ProductAttributeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['list'] = [
      'title' => t('List values'),
      'weight' => 0,
      'url' => $entity->toUrl('overview-form'),
    ];
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit attribute');
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = t('Delete attribute');
    }

    return $operations;
  }


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Attribute name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}
