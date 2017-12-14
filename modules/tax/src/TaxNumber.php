<?php

namespace Drupal\commerce_tax;

/**
 * Represents a tax number.
 *
 * Most often used to determine if the user has a vat exemption status.
 */
class TaxNumber {
  
  /**
   * The string that represents the tax id / number.
   *
   * @var string
   */
  protected $tax_number;

  /**
   * Constructs a tax number object.
   *
   * @param String $tax_number
   *   The tax number string.
   */
  public function __construct(String $tax_number) {
    $this->tax_number = $tax_number;
  }

  /**
   * Returns the tax number.
   *
   * @return string
   *   The tax number string.
   */
  public function getTaxNumber() {
    return $this->tax_number;
  }

  /**
   * Sets the tax number.
   *
   * @param String $tax_number
   *   The tax number string.
   */
  public function setTaxNumber(String $tax_number) {
    $this->tax_number = $tax_number;
  }

  /**
   * Checks if the basic format of the id has (obvious) errors.
   */
  public function isValidFormat() {
    // As the number formats differ wildly internationally it will be hard
    // to find a common ruleset to validate basic formatting.
    if (strlen($this->tax_number) < 2) {
      return FALSE;
    }

    return TRUE;
  }

}
