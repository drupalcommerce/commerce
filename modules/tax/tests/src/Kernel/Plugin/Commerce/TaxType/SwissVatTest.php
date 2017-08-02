<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\Plugin\Commerce\TaxType\SwissVat;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\SwissVat
 * @group commerce
 */
class SwissVatTest extends EuropeanUnionVatTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $configuration = [
      '_entity_id' => 'swiss_vat',
      'display_inclusive' => TRUE,
    ];
    $this->plugin = SwissVat::create($this->container, $configuration, 'swiss_vat', ['label' => 'Swiss VAT']);
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    // Swiss customer, Swiss store, standard VAT.
    $order = $this->buildOrder('CH', 'CH');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('swiss_vat|ch|standard', $adjustment->getSourceId());

    // Liechtenstein customer, Swiss store, standard VAT.
    $order = $this->buildOrder('LI', 'CH');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('swiss_vat|ch|standard', $adjustment->getSourceId());

    // Serbian customer, Swiss store, no VAT.
    $order = $this->buildOrder('RS', 'CH');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $this->assertCount(0, $adjustments);
  }

}
