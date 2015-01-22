<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\CommerceTaxTypeImporterInterface.
 */

namespace Drupal\commerce_tax;

/**
 * Defines a tax type importer.
 */
interface CommerceTaxTypeImporterInterface {

  /**
   * Returns all importable tax types.
   *
   * @return \CommerceGuys\Tax\Model\TaxTypeInterface
   *   Array of importable tax types.
   */
  public function getImportableTaxTypes();

  /**
   * Creates a tax type entity from an id.
   *
   * @param id
   *   The id of a tax type.
   *
   * @return \CommerceGuys\Tax\Model\TaxTypeInterface
   */
  public function createTaxType($taxTypeId);

}
