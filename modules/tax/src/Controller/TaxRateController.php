<?php
namespace Drupal\commerce_tax\Controller;

use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for tax rates.
 */
class TaxRateController extends ControllerBase {

  /**
   * Provides the commerce_tax_rate add form.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $commerce_tax_type
   *   The tax type.
   *
   * @return array
   *   The add form.
   */
  public function addForm(TaxTypeInterface $commerce_tax_type) {
    $rate = $this
      ->entityTypeManager()
      ->getStorage('commerce_tax_rate')
      ->create(['type' => $commerce_tax_type->id()]);

    return $this->entityFormBuilder()->getForm($rate, 'add');
  }

  /**
   * Provides the commerce_tax_rate listing.
   *
   * @param \Drupal\commerce_tax\Entity\TaxTypeInterface $commerce_tax_type
   *   The tax type.
   *
   * @return array
   *   The listing render array.
   */
  public function buildList(TaxTypeInterface $commerce_tax_type) {
    $list_builder = $this->entityTypeManager()->getListBuilder('commerce_tax_rate');
    $list_builder->setTaxType($commerce_tax_type);
    return $list_builder->render();
  }

}
