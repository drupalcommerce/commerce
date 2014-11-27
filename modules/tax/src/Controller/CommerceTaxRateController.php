<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\CommerceTaxRateController.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
  public function addForm($commerce_tax_type) {
    $rate = $this
      ->entityManager()
      ->getStorage('commerce_tax_rate')
      ->create(array('type' => $commerce_tax_type));

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
  public function buildList($commerce_tax_type) {
    $build = array();
    $list_builder = $this->entityManager()->getListBuilder('commerce_tax_rate');

    $build['commerce_tax_rates_table'] = $list_builder->setTaxType($commerce_tax_type)->render();
    return $build;
  }

}
