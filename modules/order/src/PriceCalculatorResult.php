<?php

namespace Drupal\commerce_order;

use Drupal\commerce_price\Price;

/**
 * Represents the result of a price calculation.
 *
 * @see \Drupal\commerce_order\PriceCalculatorInterface
 */
final class PriceCalculatorResult {

  /**
   * The calculated price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $calculatedPrice;

  /**
   * The base price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $basePrice;

  /**
   * The adjustments.
   *
   * @var \Drupal\commerce_order\Adjustment[]
   */
  protected $adjustments;

  /**
   * Constructs a new PriceCalculatorResult object.
   *
   * @param \Drupal\commerce_price\Price $calculated_price
   *   The calculated price.
   * @param \Drupal\commerce_price\Price $base_price
   *   The base price.
   * @param \Drupal\commerce_order\Adjustment[] $adjustments
   *   The adjustments.
   */
  public function __construct(Price $calculated_price, Price $base_price, array $adjustments = []) {
    $this->calculatedPrice = $calculated_price;
    $this->basePrice = $base_price;
    $this->adjustments = $adjustments;
  }

  /**
   * Gets the calculated price.
   *
   * This is the resolved unit price with adjustments applied.
   *
   * @return \Drupal\commerce_price\Price
   *   The calculated price.
   */
  public function getCalculatedPrice() {
    return $this->calculatedPrice;
  }

  /**
   * Gets the base price.
   *
   * This is the resolved unit price without any adjustments.
   *
   * @return \Drupal\commerce_price\Price
   *   The base price.
   */
  public function getBasePrice() {
    return $this->basePrice;
  }

  /**
   * Gets the adjustments.
   *
   * @return \Drupal\commerce_order\Adjustment[]
   *   The adjustments.
   */
  public function getAdjustments() {
    return $this->adjustments;
  }

}
