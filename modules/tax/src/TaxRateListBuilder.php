<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxRateListBuilder.
 */

namespace Drupal\commerce_tax;

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
   * @var string
   */
  protected $taxType;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['name'] = $this->t('Name');
    $header['display_name'] = $this->t('Display name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->getId();
    $row['name'] = $this->getLabel($entity);
    $row['display_name'] = $entity->getDisplayName();
    return $row + parent::buildRow($entity);
  }

  /**
   * Sets the tax type.
   *
   * @param string $taxType
   *
   * @return \Drupal\commerce_tax\TaxRateListBuilder
   */
  public function setTaxType($taxType) {
    $this->taxType = $taxType;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->storage->loadByProperties([
      'type' => $this->taxType,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $rateAmountsRoute = Url::fromRoute('entity.commerce_tax_rate_amount.collection', [
      'commerce_tax_rate' => $entity->getId()
    ]);
    $addRateAmountRoute = Url::fromRoute('entity.commerce_tax_rate_amount.add_form', [
      'commerce_tax_rate' => $entity->getId(),
    ]);

    $operations['rate_amounts'] = [
      'title' => $this->t('View rate amounts'),
      'url' => $rateAmountsRoute,
    ];
    $operations['add_rate_amount'] = [
      'title' => $this->t('Add rate amount'),
      'url' => $addRateAmountRoute,
    ];

    return $operations;
  }

}
