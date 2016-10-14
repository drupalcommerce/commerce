<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce_price\Calculator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Calculator class.
 *
 * @group commerce
 * @coversDefaultClass \Drupal\commerce_price\Calculator
 */
class CalculatorTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'commerce',
    'commerce_price',
  ];

  /**
   * @covers ::add
   * @covers ::subtract
   * @covers ::multiply
   * @covers ::divide
   * @covers ::ceil
   * @covers ::floor
   * @covers ::compare
   * @covers ::trim
   * @covers ::round
   */
  public function testCalculator() {
    $sum = Calculator::add('5', '6');
    $this->assertEquals('11', $sum);
    $difference = Calculator::subtract($sum, '20');
    $this->assertEquals('-9', $difference);
    $product = Calculator::multiply('11', '12');
    $this->assertEquals('132', $product);
    $quotient = Calculator::divide($product, '11');
    $this->assertEquals('12', $quotient);

    $ceil = Calculator::ceil('4.4');
    $this->assertEquals('5', $ceil);
    $floor = Calculator::floor('4.8');
    $this->assertEquals('4', $floor);

    $this->assertEquals('0', Calculator::compare('1', '1'));
    $this->assertEquals('1', Calculator::compare('2', '1'));
    $this->assertEquals('-1', Calculator::compare('1', '2'));

    $this->assertEquals('3', Calculator::trim('3.00'));
    $this->assertEquals('3.03', Calculator::trim('3.030'));

    $this->assertEquals('4', Calculator::round('4.3'));
    $this->assertEquals('5', Calculator::round('4.5'));
    $this->assertEquals('5', Calculator::round('4.678'));
    $this->assertEquals('4.68', Calculator::round('4.678', 2));
    $this->assertEquals('4.678', Calculator::round('4.678', 3));
    $this->assertEquals('-44.7', Calculator::round('-44.678', 1));
  }

}
