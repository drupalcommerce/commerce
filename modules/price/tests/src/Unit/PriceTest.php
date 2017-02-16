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
   * Tests creating a price with an invalid number.
   *
   * ::covers __construct.
   */
  public function testInvalidNumber() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $price = new Price('INVALID', 'USD');
  }

  /**
   * Tests creating a price with an invalid currency code.
   *
   * ::covers __construct.
   */
  public function testInvalidCurrencyCode() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $price = new Price('10', 'INVALID');
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
   * ::covers isZero
   * ::covers equals
   * ::covers greaterThan
   * ::covers greaterThanOrEqual
   * ::covers lessThan
   * ::covers lessThanOrEqual
   * ::covers compareTo.
   */
  public function testComparison() {
    $this->assertEmpty($this->price->isZero());
    $zero_price = new Price('0', 'USD');
    $this->assertNotEmpty($zero_price->isZero());

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
