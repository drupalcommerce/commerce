<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\StoreTax
 * @group commerce
 */
class StoreTaxTest extends OrderKernelTestBase {

  /**
   * The store tax.
   *
   * @var \Drupal\commerce_tax\StoreTaxInterface
   */
  protected $storeTax;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A sample tax type.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $taxType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_tax',
    'commerce_tax_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['commerce_tax']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    $this->store->set('address', [
      'country_code' => 'FR',
      'locality' => 'Paris',
      'postal_code' => '75002',
      'address_line1' => '38 Rue du Sentier',
    ]);
    $this->store->set('prices_include_tax', TRUE);
    $this->store->save();

    $this->storeTax = $this->container->get('commerce_tax.store_tax');

    $tax_type = TaxType::create([
      'id' => 'eu_vat',
      'label' => 'EU VAT',
      'plugin' => 'european_union_vat',
      'configuration' => [
        'display_inclusive' => TRUE,
      ],
      'status' => TRUE,
    ]);
    $tax_type->save();
    $this->taxType = $this->reloadEntity($tax_type);

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * @covers ::getDefaultTaxType
   */
  public function testDefaultTaxType() {
    $tax_type = $this->storeTax->getDefaultTaxType($this->store);
    $this->assertNotNull($tax_type);
    $this->assertEquals($this->taxType->id(), $tax_type->id());

    // Confirm that disabled tax types are not returned.
    $this->taxType->setStatus(FALSE);
    $this->taxType->save();
    $this->storeTax->clearCaches();
    $tax_type = $this->storeTax->getDefaultTaxType($this->store);
    $this->assertNull($tax_type);

    // Confirm that non-display-inclusive tax types are not returned.
    $this->taxType->setStatus(TRUE);
    $this->taxType->setPluginConfiguration([
      'display_inclusive' => FALSE,
    ]);
    $this->taxType->save();
    $this->storeTax->clearCaches();
    $tax_type = $this->storeTax->getDefaultTaxType($this->store);
    $this->assertNull($tax_type);

    // Confirm that non-applicable tax types are not returned.
    $second_store = $this->createStore('Second store', 'admin2@example.com', 'online', FALSE, 'US');
    $tax_type = $this->storeTax->getDefaultTaxType($second_store);
    $this->assertNull($tax_type);
  }

  /**
   * @covers ::getDefaultZones
   */
  public function testDefaultZones() {
    $zones = $this->storeTax->getDefaultZones($this->store);
    $this->assertCount(1, $zones);
    $zone = reset($zones);
    $this->assertEquals('fr', $zone->getId());

    // Confirm that no zones are returned when no tax types apply.
    $second_store = $this->createStore('Second store', 'admin2@example.com', 'online', FALSE, 'US');
    $zones = $this->storeTax->getDefaultZones($second_store);
    $this->assertCount(0, $zones);
  }

  /**
   * @covers ::getDefaultRates
   */
  public function testDefaultRates() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    $rates = $this->storeTax->getDefaultRates($this->store, $order_item);
    $this->assertCount(1, $rates);
    $this->assertArrayHasKey('fr', $rates);
    $rate = reset($rates);
    $this->assertEquals('standard', $rate->getId());

    // Confirm that the commerce_tax_test TaxRateResolver is called.
    $order_item->setQuantity('30');
    $order_item->save();
    $rates = $this->storeTax->getDefaultRates($this->store, $order_item);
    $this->assertCount(1, $rates);
    $this->assertArrayHasKey('fr', $rates);
    $rate = reset($rates);
    $this->assertEquals('reduced', $rate->getId());

    // Confirm that no rates are returned when no tax types apply.
    $second_store = $this->createStore('Second store', 'admin2@example.com', 'online', FALSE, 'US');
    $rates = $this->storeTax->getDefaultRates($second_store, $order_item);
    $this->assertCount(0, $rates);
  }

}
