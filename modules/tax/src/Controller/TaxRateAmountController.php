<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Controller\TaxRateAmountController.
 */

namespace Drupal\commerce_tax\Controller;

use Drupal\commerce_tax\Entity\TaxRateInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for tax rates.
 */
class TaxRateAmountController extends ControllerBase {

  /**
   * Provides the commerce_tax_rate_amount add form.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $commerce_tax_rate
   *   The tax rate.
   *
   * @return array
   *   The add form.
   */
  public function addForm(TaxRateInterface $commerce_tax_rate) {
    $rate = $this
      ->entityTypeManager()
      ->getStorage('commerce_tax_rate')
      ->create(['rate' => $commerce_tax_rate->id()]);

    return $this->entityFormBuilder()->getForm($rate, 'add');
  }

  /**
   * Provides the commerce_tax_rate_amount listing.
   *
   * @param \Drupal\commerce_tax\Entity\TaxRateInterface $commerce_tax_rate
   *   The tax rate.
   *
   * @return array
   *   The listing render array.
   */
  public function buildList(TaxRateInterface $commerce_tax_rate) {
    $list_builder = $this->entityTypeManager()->getListBuilder('commerce_tax_rate_amount');
    $list_builder->setTaxRate($commerce_tax_rate);
    return $list_builder->render();
  }

}
