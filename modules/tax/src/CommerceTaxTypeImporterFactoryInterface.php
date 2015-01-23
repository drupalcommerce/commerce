<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\CommerceTaxTypeImporterFactoryInterface.
 */

namespace Drupal\commerce_tax;

/**
 * Defines a tax type importer factory.
 */
interface CommerceTaxTypeImporterFactoryInterface {

  /**
   * Creates an instance of a CommerceTaxTypeImporter.
   *
   * @param string $taxTypesFolder
   *   The tax types folder of definitions.
   *
   * @return \Drupal\commerce_tax\CommerceTaxTypeImporterInterface
   *   A tax type importer.
   */
  public function createInstance($taxTypesFolder);

}
