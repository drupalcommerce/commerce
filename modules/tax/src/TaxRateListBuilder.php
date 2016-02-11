<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of tax rates.
 */
class TaxRateListBuilder extends ConfigEntityListBuilder {

  /**
   * The tax type.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $taxType;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * Sets the tax type.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type
   *   The tax type.
   *
   * @return $this
   */
  public function setTaxType(TaxTypeInterface $tax_type) {
    $this->taxType = $tax_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\commerce_tax\Entity\TaxRateInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['rate_amounts'] = [
      'title' => $this->t('View rate amounts'),
      'url' => Url::fromRoute('entity.commerce_tax_rate_amount.collection', [
        'commerce_tax_rate' => $entity->id()
      ]),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return array_keys($this->taxType->getRates());
  }

}
