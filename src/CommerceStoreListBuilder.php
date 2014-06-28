<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceStoreListBuilder.
 */

namespace Drupal\commerce;

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
    $header['mail'] = t('E-mail');
    $header['default_currency'] = t('Currency');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce\Entity\CommerceStore */
    $row['name'] = $entity->getName();
    $row['mail'] = $entity->getEmail();
    $row['default_currency'] = $entity->getDefaultCurrency();
    return $row + parent::buildRow($entity);
  }
}
