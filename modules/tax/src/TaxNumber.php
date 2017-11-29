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
   */
  public function __construct(String $tax_number) {
    $this->tax_number = $tax_number;
  }

  public function getTaxNumber() {
    return $this->tax_number;
  }

  public function setTaxNumber(String $tax_number) {
    $this->tax_number = $tax_number;
  }

  /**
   * Checks if the basic format of the id has (obvious) errors.
   */
  public function isValidFormat() {

  }

  public function getCountryCode() {
    $tax_id_country_code = mb_substr($this->getTaxNumber(), 0, 2);

    return $tax_id_country_code;
  }

}