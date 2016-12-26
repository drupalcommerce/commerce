<?php

namespace Drupal\commerce_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class LogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = t('Type');
    $header['time'] = t('Time');
    $header['log'] = t('Log');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_log\Entity\LogInterface $entity */
    $row['type'] = $entity->getCategory()->getLabel();
    $row['time'] = $entity->getCreatedTime();
    $row['log'] = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity);
    return $row + parent::buildRow($entity);
  }

}
