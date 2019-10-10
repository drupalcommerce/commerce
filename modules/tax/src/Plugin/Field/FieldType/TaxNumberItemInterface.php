<?php

namespace Drupal\commerce_tax\Plugin\Field\FieldType;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for tax number field items.
 */
interface TaxNumberItemInterface extends FieldItemInterface {

  /**
   * Applies the given verification result.
   *
   * Ensures each portion of the result is stored in the field.
   *
   * @param \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult $result
   *   The verification result.
   *
   * @return $this
   */
  public function applyVerificationResult(VerificationResult $result);

  /**
   * Checks whether the current value can be used for tax calculation.
   *
   * Confirms that:
   * - The type is correct.
   * - The number is not empty.
   * - The number has been verified, or that unverified numbers are
   *   allowed when hen the verification web service is unavailable.
   *   This check is skipped if the type does not support verification.
   *
   * @param string $expected_type
   *   The expected tax number type.
   *
   * @return bool
   *   TRUE if the current value can be used, FALSE otherwise.
   */
  public function checkValue($expected_type);

  /**
   * Gets the allowed countries.
   *
   * Tax numbers will be collected only for these countries.
   *
   * @return string[]
   *   A list of country codes.
   */
  public function getAllowedCountries();

  /**
   * Gets the allowed tax number types.
   *
   * Determined based on the allowed countries.
   *
   * @return string[]
   *   A list of plugin IDs.
   */
  public function getAllowedTypes();

  /**
   * Gets the tax number type plugin.
   *
   * @return \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\TaxNumberTypeInterface
   *   The tax number type plugin.
   */
  public function getTypePlugin();

}
