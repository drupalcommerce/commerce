<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_price\Price
 * @group commerce
 */
class PriceTest extends UnitTestCase {

  /**
   * @var \Drupal\commerce_price\Price
   */
  protected $price;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->price = new Price(100, 'CAD');
  }

  /**
   * Test that floating point numbers causes an exception to be thrown.
   */
  public function testInvalidArgumentException() {
    $this->setExpectedException('\InvalidArgumentException');
    $this->price->multiply(1.01);
  }

  /**
   * Test that using two prices with different currencies causes an exception to be thrown.
   */
  public function testCurrencyMismatchException() {
    $this->setExpectedException('\Drupal\commerce_price\Exception\CurrencyMismatchException');
    $diffCurrencyPrice = new Price(100, 'USD');
    $this->price->add($diffCurrencyPrice);
  }

  /**
   * Test that trailing zeroes and unneccessary decimals are properly removed.
   */
  public function testTrailingZeroesRemoved() {

    $trailingZeroPriceA = new Price('150.17000', 'CAD');
    $trailingZeroPriceB = new Price('0.000', 'CAD');
    $result = $trailingZeroPriceA->add($trailingZeroPriceB);

    $this->assertSame('150.17', $result->getDecimalAmount());

    $trailingZeroPriceA = new Price('150.000', 'CAD');
    $result = $trailingZeroPriceA->add($trailingZeroPriceB);

    $this->assertSame('150', $result->getDecimalAmount());
  }

}
