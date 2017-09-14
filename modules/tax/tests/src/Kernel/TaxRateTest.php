<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_tax\TaxRate;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxRate
 * @group commerce
 */
class TaxRateTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testMissingProperty() {
    $definition = [
      'id' => 'test',
    ];
    $rate = new TaxRate($definition);
  }

  /**
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidAmounts() {
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'amounts' => 'WRONG',
    ];
    $rate = new TaxRate($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getId
   * @covers ::getLabel
   * @covers ::getAmounts
   * @covers ::getAmount
   * @covers ::isDefault
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'id' => 'standard',
      'label' => 'Standard',
      'amounts' => [
        ['amount' => '0.23', 'start_date' => '2012-01-01'],
      ],
      'default' => TRUE,
    ];
    $rate = new TaxRate($definition);

    $this->assertEquals($definition['id'], $rate->getId());
    $this->assertEquals($definition['label'], $rate->getLabel());
    $this->assertTrue($rate->isDefault());
    $this->assertCount(1, $rate->getAmounts());

    $amount = $rate->getAmount();
    $this->assertEquals($amount, $rate->getAmounts()[0]);
    $this->assertEquals($definition['amounts'][0]['amount'], $amount->getAmount());
  }

}
