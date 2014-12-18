<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxRateAmountListBuilder.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use CommerceGuys\Tax\Model\TaxRateInterface;

/**
 * Provides a listing of tax rates.
 */
class CommerceTaxRateAmountListBuilder extends ConfigEntityListBuilder {

  /**
   * The tax rate.
   *
   * @var string
   */
  protected $tax_rate;

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
   * @param string $tax_rate
   *
   * @return \Drupal\commerce_tax\Controller\CommerceTaxRateAmountListBuilder
   */
  public function setTaxRate($tax_rate) {
    $this->tax_rate = $tax_rate;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->storage->loadByProperties(array(
      'rate' => $this->tax_rate,
    ));
  }

}
