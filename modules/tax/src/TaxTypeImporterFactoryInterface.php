<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxTypeImporterFactoryInterface.
 */

namespace Drupal\commerce_tax;

/**
 * Defines a tax type importer factory.
 */
interface TaxTypeImporterFactoryInterface {

  /**
   * Creates an instance of a TaxTypeImporter.
   *
   * @param string $taxTypesFolder
   *   The tax types folder of definitions.
   *
   * @return \Drupal\commerce_tax\TaxTypeImporterInterface
   *   A tax type importer.
   */
  public function createInstance($taxTypesFolder);

}
