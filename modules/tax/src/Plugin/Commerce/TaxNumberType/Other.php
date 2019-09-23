<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

/**
 * Provides the Other tax number type.
 *
 * Not restricted by country, provides no validation or verification.
 *
 * Used as a fallback when a country-specific plugin doesn't exist yet
 * or has disappeared from the system, allowing previously-entered values
 * to be viewed, and new values to be entered.
 *
 * @CommerceTaxNumberType(
 *   id = "other",
 *   label = "Other",
 * )
 */
class Other extends TaxNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function validate($tax_number) {
    return TRUE;
  }

}
