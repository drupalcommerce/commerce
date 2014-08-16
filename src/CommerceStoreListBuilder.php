<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceStoreListBuilder.
 */

namespace Drupal\commerce;

use Drupal\commerce\Entity\CommerceStoreType;
use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for stores.
 */
class CommerceStoreListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['type'] = t('Type');
    $header['mail'] = t('E-mail');
    $header['default_currency'] = t('Currency');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce\Entity\CommerceStore */
    $commerce_store_type = CommerceStoreType::load($entity->bundle());

    $row['name'] = $entity->getName();
    $row['type'] = String::checkPlain($commerce_store_type->label());
    $row['mail'] = $entity->getEmail();
    $row['default_currency'] = $entity->getDefaultCurrency();

    return $row + parent::buildRow($entity);
  }

}
