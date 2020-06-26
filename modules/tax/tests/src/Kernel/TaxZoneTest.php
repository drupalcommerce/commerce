<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use CommerceGuys\Addressing\Address;
use Drupal\commerce_tax\TaxZone;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\TaxZone
 * @group commerce
 */
class TaxZoneTest extends CommerceKernelTestBase {

  /**
   * @covers ::__construct
   */
  public function testMissingProperty() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'id' => 'test',
    ];
    new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidTerritories() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'display_label' => 'VAT',
      'territories' => 'WRONG',
    ];
    new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidRates() {
    $this->expectException(\InvalidArgumentException::class);
    $definition = [
      'id' => 'test',
      'label' => 'Test',
      'display_label' => 'VAT',
      'territories' => [
        ['country_code' => 'RS'],
      ],
      'rates' => 'WRONG',
    ];
    new TaxZone($definition);
  }

  /**
   * @covers ::__construct
   * @covers ::getId
   * @covers ::getLabel
   * @covers ::getDisplayLabel
   * @covers ::getTerritories
   * @covers ::getRates
   * @covers ::getRate
   * @covers ::getDefaultRate
   * @covers ::match
   * @covers ::toArray
   */
  public function testValid() {
    // Can't use a unit test because DrupalDateTime objects use \Drupal.
    $definition = [
      'id' => 'ie',
      'label' => 'Ireland',
      'display_label' => 'VAT',
      'territories' => [
        ['country_code' => 'IE'],
      ],
      'rates' => [
        [
          'id' => 'standard',
          'label' => 'Standard',
          'percentages' => [
            ['number' => '0.23', 'start_date' => '2012-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ];
    $zone = new TaxZone($definition);

    $this->assertEquals($definition['id'], $zone->getId());
    $this->assertEquals($definition['label'], $zone->getLabel());
    $this->assertEquals($definition['display_label'], $zone->getDisplayLabel());
    $this->assertCount(1, $zone->getTerritories());
    $this->assertEquals($definition['territories'][0]['country_code'], $zone->getTerritories()[0]->getCountryCode());
    $this->assertCount(1, $zone->getRates());
    $this->assertArrayHasKey('standard', $zone->getRates());
    $rate = $zone->getRates()['standard'];
    $this->assertEquals($definition['rates'][0]['label'], $rate->getLabel());
    $this->assertEquals($rate, $zone->getRate('standard'));
    $this->assertNull($zone->getRate('reduced'));
    $this->assertEquals($rate, $zone->getDefaultRate());

    $irish_address = new Address('IE');
    $serbian_address = new Address('RS');
    $this->assertTrue($zone->match($irish_address));
    $this->assertFalse($zone->match($serbian_address));
    $this->assertEquals($definition, $zone->toArray());
  }

}
