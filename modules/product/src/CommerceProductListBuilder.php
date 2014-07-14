<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceProductListBuilder.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for stores.
 */
class CommerceProductListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['sku'] = t('SKU');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_product\Entity\CommerceProduct */
    $row['title'] = $entity->getTitle();
    $row['sku'] = $entity->getSku();
    return $row + parent::buildRow($entity);
  }
}
