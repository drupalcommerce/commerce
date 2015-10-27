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
    $header['mail'] = t('Email');
    $header['default_currency'] = t('Currency');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_store\Entity\Store */
    $storeType = StoreType::load($entity->bundle());

    $row['name'] = $entity->getName();
    $row['type'] = $storeType->label();
    $row['mail'] = $entity->getEmail();
    $row['default_currency'] = $entity->getDefaultCurrency();

    return $row + parent::buildRow($entity);
  }

}
