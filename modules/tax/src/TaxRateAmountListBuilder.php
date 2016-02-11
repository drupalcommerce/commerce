<?php

namespace Drupal\commerce_tax;

use Drupal\commerce_tax\Entity\TaxRateInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of tax rates.
 */
class TaxRateAmountListBuilder extends ConfigEntityListBuilder {

  /**
   * The tax rate.
   *
   * @var \Drupal\commerce_tax\Entity\TaxRateInterface
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
    $row['id'] = $entity->id();
    $row['amount'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * Sets the tax rate.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $tax_rate
   *   The tax rate.
   *
   * @return $this
   */
  public function setTaxRate(TaxRateInterface $tax_rate) {
    $this->taxRate = $tax_rate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return array_keys($this->taxRate->getAmounts());
  }

}
