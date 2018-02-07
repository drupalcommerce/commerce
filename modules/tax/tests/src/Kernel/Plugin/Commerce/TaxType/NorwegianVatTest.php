<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\Plugin\Commerce\TaxType\NorwegianVat;

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

    $configuration = [
      '_entity_id' => 'norwegian_vat',
      'display_inclusive' => TRUE,
    ];
    $this->plugin = NorwegianVat::create($this->container, $configuration, 'norwegian_vat', ['label' => 'Norwegian VAT']);
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    // Norwegian customer, Norwegian store, standard VAT.
    $order = $this->buildOrder('NO', 'NO');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('norwegian_vat|no|standard', $adjustment->getSourceId());

    // Polish customer, Norwegian store, no VAT.
    $order = $this->buildOrder('PL', 'NO');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $this->assertCount(0, $adjustments);
  }

}
