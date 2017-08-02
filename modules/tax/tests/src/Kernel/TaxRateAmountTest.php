<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_tax\TaxRateAmount;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxRateAmount
 * @group commerce
 */
class TaxRateAmountTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMissingProperty() {
    $definition = [
      'amount' => '0.23',
    ];
    $amount = new TaxRateAmount($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getAmount
   * @covers ::getStartDate
   * @covers ::getEndDate
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'amount' => '0.23',
      'start_date' => '2012-01-01',
    ];
    $amount = new TaxRateAmount($definition);

    $this->assertEquals($definition['amount'], $amount->getAmount());
    $this->assertEquals(new DrupalDateTime($definition['start_date']), $amount->getStartDate());
    $this->assertNull($amount->getEndDate());

    $definition['end_date'] = '2012-12-31';
    $amount = new TaxRateAmount($definition);
    $this->assertEquals(new DrupalDateTime($definition['end_date']), $amount->getEndDate());
  }

}
