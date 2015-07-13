<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductVariationTypeListBuilder.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for product variation types.
 */
class ProductVariationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Product variation type');
    $header['type'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['name'] = $this->getLabel($entity);
    $row['type'] = $entity->id();

   return $row + parent::buildRow($entity);
  }

}
