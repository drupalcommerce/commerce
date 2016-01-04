<?php

/**
 * @file
 * Contains \Drupal\commerce_store\StoreListBuilder.
 */

namespace Drupal\commerce_store;

use Drupal\commerce_store\Entity\StoreType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for stores.
 */
class StoreListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['type'] = t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_store\Entity\Store */
    $store_type = StoreType::load($entity->bundle());

    $row['name']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
    ] + $entity->urlInfo()->toRenderArray();
    $row['type'] = $store_type->label();

    return $row + parent::buildRow($entity);
  }

}
