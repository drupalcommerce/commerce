<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxRateController.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for tax rates.
 */
class CommerceTaxRateController extends ControllerBase {

  /**
   * Returns a rendered add form to create a new tax rate associated to the given tax type.
   *
   * @param string
   *   The commerce_tax_type id.
   *
   * @return array
   *   The commerce_tax_rate add form.
   */
  public function addForm($commerceTaxType) {
    $rate = $this
      ->entityManager()
      ->getStorage('commerce_tax_rate')
      ->create(array('type' => $commerceTaxType));

    return $this->entityFormBuilder()->getForm($rate, 'add');
  }

  /**
   * Returns a rendered list of tax rates entities associated to the given tax type.
   *
   * @param string
   *   The commerce_tax_type id.
   *
   * @return array
   *   The list of commerce_tax_rates.
   */
  public function buildList($commerceTaxType) {
    $build = array();
    $listBuilder = $this->entityManager()->getListBuilder('commerce_tax_rate');

    $build['commerce_tax_rates_table'] = $listBuilder->setTaxType($commerceTaxType)->render();
    return $build;
  }

}
