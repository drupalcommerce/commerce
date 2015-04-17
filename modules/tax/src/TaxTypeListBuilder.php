<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxTypeListBuilder.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of tax types.
 */
class TaxTypeListBuilder extends ConfigEntityListBuilder {

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

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $ratesRoute = Url::fromRoute('entity.commerce_tax_rate.collection', [
      'commerce_tax_type' => $entity->getId()
    ]);
    $addRateRoute = Url::fromRoute('entity.commerce_tax_rate.add_form', [
      'commerce_tax_type' => $entity->getId(),
    ]);

    $operations['rates'] = [
      'title' => $this->t('View rates'),
      'url' => $ratesRoute,
    ];
    $operations['add_rate'] = [
      'title' => $this->t('Add rate'),
      'url' => $addRateRoute,
    ];

    return $operations;
  }

}
