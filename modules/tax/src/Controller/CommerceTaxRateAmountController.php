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
  public function addForm($commerce_tax_rate) {
    $rate_amount = $this
      ->entityManager()
      ->getStorage('commerce_tax_rate_amount')
      ->create(array('rate' => $commerce_tax_rate));

    return $this->entityFormBuilder()->getForm($rate_amount, 'add');
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
  public function buildList($commerce_tax_rate) {
    $build = array();
    $list_builder = $this->entityManager()->getListBuilder('commerce_tax_rate_amount');

    $build['commerce_tax_rate_amounts_table'] = $list_builder->setTaxRate($commerce_tax_rate)->render();
    return $build;
  }

}
