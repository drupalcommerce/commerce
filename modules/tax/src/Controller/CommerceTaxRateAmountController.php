<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxRateAmountController.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for tax rates.
 */
class CommerceTaxRateAmountController extends ControllerBase {

  /**
   * Returns a rendered add form to create a new tax rate amount associated to the given tax rate.
   *
   * @param string
   *   The commerce_tax_rate id.
   *
   * @return array
   *   The commerce_tax_rate_amount add form.
   */
  public function addForm($commerceTaxRate) {
    $rateAmount = $this
      ->entityManager()
      ->getStorage('commerce_tax_rate_amount')
      ->create(array('rate' => $commerceTaxRate));

    return $this->entityFormBuilder()->getForm($rateAmount, 'add');
  }

  /**
   * Returns a rendered list of tax rate amounts entities associated to the given tax rate.
   *
   * @param string
   *   The commerce_tax_rate id.
   *
   * @return array
   *   The list of commerce_tax_rate_amounts.
   */
  public function buildList($commerceTaxRate) {
    $build = array();
    $listBuilder = $this->entityManager()->getListBuilder('commerce_tax_rate_amount');

    $build['commerce_tax_rate_amounts_table'] = $listBuilder->setTaxRate($commerceTaxRate)->render();
    return $build;
  }

}
