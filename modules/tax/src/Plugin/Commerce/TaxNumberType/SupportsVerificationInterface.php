<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

interface SupportsVerificationInterface {

  /**
   * Verifies the given tax number.
   *
   * @param string $tax_number
   *   The tax number.
   *
   * @return \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult
   *   The verification result.
   */
  public function verify($tax_number);

  /**
   * Renders the given verification result.
   *
   * @param \Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult $result
   *   The verification result.
   *
   * @return array
   *   The render array.
   */
  public function renderVerificationResult(VerificationResult $result);

}
