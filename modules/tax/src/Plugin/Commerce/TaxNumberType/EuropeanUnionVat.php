<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

/**
 * Provides the European Union VAT tax number type.
 *
 * Note that in addition to EU members, the country list also includes
 * Isle of Man (IM), which uses GB VAT, and Monaco (MC), which uses FR VAT.
 *
 * @CommerceTaxNumberType(
 *   id = "european_union_vat",
 *   label = "European Union VAT",
 *   countries = {
 *     "EU",
 *     "AT", "BE", "BG", "CY", "CZ", "DE", "DK", "EE", "ES", "FI",
 *     "FR", "GB", "GR", "HR", "HU", "IE", "IM", "IT", "LT", "LU",
 *     "LV", "MC", "MT", "NL", "PL", "PT", "RO", "SE", "SI", "SK",
 *   },
 *   examples = {"DE123456789", "HU12345678"}
 * )
 */
class EuropeanUnionVat extends TaxNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function validate($tax_number) {
    $patterns = $this->getValidationPatterns();
    $prefix = substr($tax_number, 0, 2);
    if (!isset($patterns[$prefix])) {
      return FALSE;
    }
    $number = substr($tax_number, 2);
    if (!preg_match('/^' . $patterns[$prefix] . '$/', $number)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the validation patterns.
   *
   * Source: http://ec.europa.eu/taxation_customs/vies/faq.html#item_11
   *
   * @return array
   *   The validation patterns, keyed by prefix.
   *   The prefix is an ISO country code, except for Greece (EL instead of GR).
   */
  protected function getValidationPatterns() {
    $patterns = [
      'AT' => 'U[A-Z\d]{8}',
      'BE' => '(0\d{9}|\d{10})',
      'BG' => '\d{9,10}',
      'CY' => '\d{8}[A-Z]',
      'CZ' => '\d{8,10}',
      'DE' => '\d{9}',
      'DK' => '\d{8}',
      'EE' => '\d{9}',
      'EL' => '\d{9}',
      'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
      'FI' => '\d{8}',
      'FR' => '[0-9A-Z]{2}\d{9}',
      'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
      'HR' => '\d{11}',
      'HU' => '\d{8}',
      'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',
      'IT' => '\d{11}',
      'LT' => '(\d{9}|\d{12})',
      'LU' => '\d{8}',
      'LV' => '\d{11}',
      'MT' => '\d{8}',
      'NL' => '\d{9}B\d{2}',
      'PL' => '\d{10}',
      'PT' => '\d{9}',
      'RO' => '\d{2,10}',
      'SE' => '\d{12}',
      'SI' => '\d{8}',
      'SK' => '\d{10}',
    ];

    return $patterns;
  }

}
