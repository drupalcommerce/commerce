<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxRateListBuilder.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use CommerceGuys\Tax\Model\TaxTypeInterface;

/**
 * Provides a listing of tax rates.
 */
class CommerceTaxRateListBuilder extends ConfigEntityListBuilder {

  /**
   * The tax type.
   *
   * @var string
   */
  protected $tax_type;

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
   * @param string $tax_type
   *
   * @return \Drupal\commerce_tax\Controller\CommerceTaxRateListBuilder
   */
  public function setTaxType($tax_type) {
    $this->tax_type = $tax_type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->storage->loadByProperties(array(
      'type' => $this->tax_type,
    ));
  }

}
