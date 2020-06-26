<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\Entity\TaxType;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\NorwegianVat
 * @group commerce
 */
class NorwegianVatTest extends EuropeanUnionVatTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->taxType = TaxType::create([
      'id' => 'norwegian_vat',
      'label' => 'Norwegian VAT',
      'plugin' => 'norwegian_vat',
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
    // Norwegian customer, Norwegian store, standard VAT.
    $order = $this->buildOrder('NO', 'NO');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('norwegian_vat|no|standard', $adjustment->getSourceId());

    // Polish customer, Norwegian store, no VAT.
    $order = $this->buildOrder('PL', 'NO');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $this->assertCount(0, $adjustments);
  }

  /**
   * @covers ::getZones
   */
  public function testGetZones() {
    /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $plugin */
    $plugin = $this->taxType->getPlugin();
    $zones = $plugin->getZones();
    $this->assertArrayHasKey('no', $zones);
  }

}
