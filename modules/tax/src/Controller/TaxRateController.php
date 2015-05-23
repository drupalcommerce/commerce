<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\TaxRateController.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for tax rates.
 */
class TaxRateController extends ControllerBase {

  /**
   * Gets a rendered add form to create a new tax rate associated to the given tax type.
   *
   * @param string
   *   The commerce_tax_type id.
   *
   * @return array
   *   The commerce_tax_rate add form.
   */
  public function addForm($commerce_tax_type) {
    $rate = $this
      ->entityManager()
      ->getStorage('commerce_tax_rate')
      ->create(['type' => $commerce_tax_type]);

    return $this->entityFormBuilder()->getForm($rate, 'add');
  }

  /**
   * Gets a rendered list of tax rates entities associated to the given tax type.
   *
   * @param string
   *   The commerce_tax_type id.
   *
   * @return array
   *   The list of commerce_tax_rates.
   */
  public function buildList($commerce_tax_type) {
    $build = [];
    $listBuilder = $this->entityManager()->getListBuilder('commerce_tax_rate');

    $build['commerce_tax_rates_table'] = $listBuilder->setTaxType($commerce_tax_type)->render();
    return $build;
  }

}
