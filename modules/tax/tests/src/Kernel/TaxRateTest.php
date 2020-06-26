<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_tax\TaxRate;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxRate
 * @group commerce
 */
class TaxRateTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   */
  public function testMissingProperty() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'id' => 'test',
    ];
    new TaxRate($definition);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidPercentages() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'percentages' => 'WRONG',
    ];
    new TaxRate($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getId
   * @covers ::getLabel
   * @covers ::getPercentages
   * @covers ::getPercentage
   * @covers ::isDefault
   * @covers ::toArray
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'id' => 'standard',
      'label' => 'Standard',
      'percentages' => [
        ['number' => '0.23', 'start_date' => '2012-01-01', 'end_date' => '2012-12-31'],
        ['number' => '0.24', 'start_date' => '2013-01-01'],
      ],
      'default' => TRUE,
    ];
    $rate = new TaxRate($definition);

    $this->assertEquals($definition['id'], $rate->getId());
    $this->assertEquals($definition['label'], $rate->getLabel());
    $this->assertTrue($rate->isDefault());
    $this->assertCount(2, $rate->getPercentages());

    $date = new DrupalDateTime('2012-06-30 12:00:00');
    $percentage = $rate->getPercentage($date);
    $this->assertEquals($percentage, $rate->getPercentages()[0]);
    $this->assertEquals($definition['percentages'][0]['number'], $percentage->getNumber());

    $date = new DrupalDateTime('2012-12-31 17:15:00');
    $percentage = $rate->getPercentage($date);
    $this->assertEquals($percentage, $rate->getPercentages()[0]);
    $this->assertEquals($definition['percentages'][0]['number'], $percentage->getNumber());

    $percentage = $rate->getPercentage();
    $this->assertEquals($percentage, $rate->getPercentages()[1]);
    $this->assertEquals($definition['percentages'][1]['number'], $percentage->getNumber());
    $this->assertEquals($definition, $rate->toArray());
  }

}
