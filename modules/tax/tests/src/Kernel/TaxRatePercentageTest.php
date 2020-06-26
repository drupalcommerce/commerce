<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_tax\TaxRatePercentage;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxRatePercentage
 * @group commerce
 */
class TaxRatePercentageTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   */
  public function testMissingProperty() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'number' => '0.23',
    ];
    new TaxRatePercentage($definition);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidNumber() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'number' => 'INVALID',
      'start_date' => '2012-01-01',
    ];
    new TaxRatePercentage($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getNumber
   * @covers ::getStartDate
   * @covers ::getEndDate
   * @covers ::toArray
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'number' => '0.23',
      'start_date' => '2012-01-01',
    ];
    $percentage = new TaxRatePercentage($definition);

    $this->assertEquals($definition['number'], $percentage->getNumber());
    $this->assertEquals(new DrupalDateTime($definition['start_date'], 'UTC'), $percentage->getStartDate());
    $this->assertNull($percentage->getEndDate());

    $definition['end_date'] = '2012-12-31';
    $percentage = new TaxRatePercentage($definition);
    $this->assertEquals(new DrupalDateTime($definition['end_date'], 'UTC'), $percentage->getEndDate());
    $this->assertEquals($definition, $percentage->toArray());
  }

  /**
   * @covers ::calculateTaxAmount
   */
  public function testCalculation() {
    $definition = [
      'number' => '0.20',
      'start_date' => '2012-01-01',
    ];
    $percentage = new TaxRatePercentage($definition);

    $tax_amount = $percentage->calculateTaxAmount(new Price('12', 'USD'), FALSE);
    $this->assertEquals(new Price('2.4', 'USD'), $tax_amount);

    $tax_amount = $percentage->calculateTaxAmount(new Price('12', 'USD'), TRUE);
    $this->assertEquals(new Price('2', 'USD'), $tax_amount);
  }

}
