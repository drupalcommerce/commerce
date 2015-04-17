<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxRateAmountListBuilder.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of tax rates.
 */
class TaxRateAmountListBuilder extends ConfigEntityListBuilder {

  /**
   * The tax rate.
   *
   * @var string
   */
  protected $taxRate;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['amount'] = $this->t('Amount');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->getId();
    $row['amount'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * Sets the tax rate.
   *
   * @param string $taxRate
   *
   * @return \Drupal\commerce_tax\TaxRateAmountListBuilder
   */
  public function setTaxRate($taxRate) {
    $this->taxRate = $taxRate;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->storage->loadByProperties([
      'rate' => $this->taxRate,
    ]);
  }

}
