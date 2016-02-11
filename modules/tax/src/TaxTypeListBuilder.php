<?php
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
    $header['name'] = $this->t('Name');
    $header['tag'] = $this->t('Tag');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $entity */
    $row['name'] = $entity->label();
    $row['tag'] = $entity->getTag();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['rates'] = [
      'title' => $this->t('View rates'),
      'url' => Url::fromRoute('entity.commerce_tax_rate.collection', ['commerce_tax_type' => $entity->id()]),
    ];

    return $operations;
  }

}
