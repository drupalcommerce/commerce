<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Price class.
 *
 * @group commerce
 * @coversDefaultClass \Drupal\commerce_price\Price
 */
class PriceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'commerce',
    'commerce_price',
  ];

  /**
   * @covers ::multiply
   * @covers ::add
   * @covers ::round
   */
  public function testPrice() {
    $price = new Price('5.99', 'USD');
    $sum = $price->multiply('0.0755')->round();
    $this->assertEquals('0.45', $sum->getNumber());
    $price = $price->add($sum);
    $this->assertEquals('6.44', $price->getNumber());
  }

}
