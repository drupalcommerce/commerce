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
   * Imports all the defined tax types.
   */
  public function import();

}
