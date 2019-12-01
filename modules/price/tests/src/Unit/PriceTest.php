<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Price class.
 *
 * @coversDefaultClass \Drupal\commerce_price\Price
 * @group commerce
 */
class PriceTest extends UnitTestCase {

  /**
   * The price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $price;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->price = new Price('10', 'USD');
  }

  /**
   * Tests creating a price from an invalid array.
   *
   * ::covers __construct.
   */
  public function testCreateFromInvalidArray() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Price::fromArray() called with a malformed array.');
    $price = Price::fromArray([]);
  }

  /**
   * Tests creating a price from a valid array.
   *
   * ::covers __construct.
   */
  public function testCreateFromValidArray() {
    $price = Price::fromArray(['number' => '10', 'currency_code' => 'USD']);
    $this->assertEquals('10', $price->getNumber());
    $this->assertEquals('USD', $price->getCurrencyCode());
    $this->assertEquals('10 USD', $price->__toString());
    $this->assertEquals(['number' => '10', 'currency_code' => 'USD'], $price->toArray());
  }

  /**
   * Tests creating a price with an invalid number.
   *
   * ::covers __construct.
   */
  public function testInvalidNumber() {
    $this->expectException(\InvalidArgumentException::class);
    $price = new Price('INVALID', 'USD');
  }

  /**
   * Tests creating a price with an invalid currency code.
   *
   * ::covers __construct.
   */
  public function testInvalidCurrencyCode() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid currency code "TEST".');
    $price = new Price('10', 'TEST');
  }

  /**
   * Tests the methods for getting the number/currency code in various formats.
   *
   * ::covers getNumber
   * ::covers getCurrencyCode
   * ::covers __toString
   * ::covers toArray.
   */
  public function testGetters() {
    $this->assertEquals('10', $this->price->getNumber());
    $this->assertEquals('USD', $this->price->getCurrencyCode());
    $this->assertEquals('10 USD', $this->price->__toString());
    $this->assertEquals(['number' => '10', 'currency_code' => 'USD'], $this->price->toArray());
  }

  /**
   * Tests addition with mismatched currencies.
   *
   * ::covers add.
   */
  public function testAddWithMismatchedCurrencies() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided prices have mismatched currencies: 10 USD, 5 EUR.');
    $this->price->add(new Price('5', 'EUR'));
  }

  /**
   * Tests subtraction with mismatched currencies.
   *
   * ::covers subtract.
   */
  public function testSubtractWithMismatchedCurrencies() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided prices have mismatched currencies: 10 USD, 4 EUR.');
    $this->price->subtract(new Price('4', 'EUR'));
  }

  /**
   * Tests the arithmetic methods.
   *
   * ::covers add
   * ::covers subtract
   * ::covers multiply
   * ::covers divide.
   */
  public function testArithmetic() {
    $result = $this->price->add(new Price('5', 'USD'));
    $this->assertEquals(new Price('15', 'USD'), $result);

    $result = $this->price->subtract(new Price('5', 'USD'));
    $this->assertEquals(new Price('5', 'USD'), $result);

    $result = $this->price->multiply('5');
    $this->assertEquals(new Price('50', 'USD'), $result);

    $result = $this->price->divide('10');
    $this->assertEquals(new Price('1', 'USD'), $result);
  }

  /**
   * Tests the comparison methods.
   *
   * ::covers isPositive
   * ::covers isNegative
   * ::covers isZero
   * ::covers equals
   * ::covers greaterThan
   * ::covers greaterThanOrEqual
   * ::covers lessThan
   * ::covers lessThanOrEqual
   * ::covers compareTo.
   */
  public function testComparison() {
    $this->assertTrue($this->price->isPositive());
    $this->assertFalse($this->price->isNegative());
    $this->assertFalse($this->price->isZero());

    $negative_price = new Price('-10', 'USD');
    $this->assertFalse($negative_price->isPositive());
    $this->assertTrue($negative_price->isNegative());
    $this->assertFalse($negative_price->isZero());

    $zero_price = new Price('0', 'USD');
    $this->assertFalse($zero_price->isPositive());
    $this->assertFalse($zero_price->isNegative());
    $this->assertTrue($zero_price->isZero());

    $this->assertNotEmpty($this->price->equals(new Price('10', 'USD')));
    $this->assertEmpty($this->price->equals(new Price('15', 'USD')));

    $this->assertNotEmpty($this->price->greaterThan(new Price('5', 'USD')));
    $this->assertEmpty($this->price->greaterThan(new Price('10', 'USD')));
    $this->assertEmpty($this->price->greaterThan(new Price('15', 'USD')));

    $this->assertNotEmpty($this->price->greaterThanOrEqual(new Price('5', 'USD')));
    $this->assertNotEmpty($this->price->greaterThanOrEqual(new Price('10', 'USD')));
    $this->assertEmpty($this->price->greaterThanOrEqual(new Price('15', 'USD')));

    $this->assertNotEmpty($this->price->lessThan(new Price('15', 'USD')));
    $this->assertEmpty($this->price->lessThan(new Price('10', 'USD')));
    $this->assertEmpty($this->price->lessThan(new Price('5', 'USD')));

    $this->assertNotEmpty($this->price->lessThanOrEqual(new Price('15', 'USD')));
    $this->assertNotEmpty($this->price->lessThanOrEqual(new Price('10', 'USD')));
    $this->assertEmpty($this->price->lessThanOrEqual(new Price('5', 'USD')));
  }

}
