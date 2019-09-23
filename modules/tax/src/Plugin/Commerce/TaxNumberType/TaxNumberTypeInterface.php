<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the base interface for tax number types.
 */
interface TaxNumberTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the tax number type label.
   *
   * @return string
   *   The tax number type label.
   */
  public function getLabel();

  /**
   * Gets the supported countries.
   *
   * @return string[]
   *   A list of country codes.
   */
  public function getCountries();

  /**
   * Gets the tax number examples.
   *
   * @return string[]
   *   The examples.
   */
  public function getExamples();

  /**
   * Gets the tax number examples, formatted for display.
   *
   * @return string
   *   The formatted examples.
   */
  public function getFormattedExamples();

  /**
   * Canonicalizes the given tax number.
   *
   * @param string $tax_number
   *   The tax number.
   *
   * @return string
   *   The canonicalized tax number.
   */
  public function canonicalize($tax_number);

  /**
   * Validates the given tax number.
   *
   * @param string $tax_number
   *   The tax number.
   *
   * @return bool
   *   TRUE if the given tax number if valid, FALSE otherwise.
   */
  public function validate($tax_number);

}
