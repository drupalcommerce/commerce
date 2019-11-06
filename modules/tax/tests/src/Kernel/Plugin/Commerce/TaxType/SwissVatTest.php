<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\Entity\TaxType;

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

    $this->taxType = TaxType::create([
      'id' => 'swiss_vat',
      'label' => 'Swiss VAT',
      'plugin' => 'swiss_vat',
      'configuration' => [
        'display_inclusive' => TRUE,
      ],
      // Don't allow the tax type to apply automatically.
      'status' => FALSE,
    ]);
    $this->taxType->save();
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    $plugin = $this->taxType->getPlugin();
    // Swiss customer, Swiss store, standard VAT.
    $order = $this->buildOrder('CH', 'CH');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('swiss_vat|ch|standard', $adjustment->getSourceId());

    // Liechtenstein customer, Swiss store, standard VAT.
    $order = $this->buildOrder('LI', 'CH');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('swiss_vat|ch|standard', $adjustment->getSourceId());

    // Serbian customer, Swiss store, no VAT.
    $order = $this->buildOrder('RS', 'CH');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $this->assertCount(0, $adjustments);
  }

}
