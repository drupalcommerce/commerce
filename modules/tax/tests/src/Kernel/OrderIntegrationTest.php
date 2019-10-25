<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests integration with orders.
 *
 * @group commerce
 */
class OrderIntegrationTest extends OrderKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['commerce_tax']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    $this->store->set('prices_include_tax', TRUE);
    $this->store->save();

    // The default store is US-WI, so imagine that the US has VAT.
    TaxType::create([
      'id' => 'us_vat',
      'label' => 'US VAT',
      'plugin' => 'custom',
      'configuration' => [
        'display_inclusive' => TRUE,
        'rates' => [
          [
            'id' => 'standard',
            'label' => 'Standard',
            'percentage' => '0.2',
          ],
        ],
        'territories' => [
          ['country_code' => 'US', 'administrative_area' => 'WI'],
          ['country_code' => 'US', 'administrative_area' => 'SC'],
        ],
      ],
    ])->save();

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
   * Tests the handling of orders without a billing profile.
   */
  public function testNoProfile() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Confirm that the store address was used.
    $adjustments = $this->order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals(new Price('2.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('us_vat|default|standard', $adjustment->getSourceId());
  }

  /**
   * Tests the handling of orders with a broken billing profile.
   */
  public function testBrokenProfile() {
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [],
    ]);
    $profile->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->setBillingProfile($profile);
    $this->order->save();

    // Confirm that the store address was used.
    $adjustments = $this->order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals(new Price('2.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('us_vat|default|standard', $adjustment->getSourceId());
  }

  /**
   * Tests the handling of orders with a valid billing profile.
   */
  public function testValidProfile() {
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'administrative_area' => 'SC',
      ],
    ]);
    $profile->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->setBillingProfile($profile);
    $this->order->save();

    $adjustments = $this->order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals(new Price('2.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('us_vat|default|standard', $adjustment->getSourceId());
  }

  /**
   * Tests the handling of tax-exempt customers with tax-inclusive prices.
   */
  public function testTaxExemptPrices() {
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'RS',
      ],
    ]);
    $profile->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('12.00', 'USD'),
      'overridden_unit_price' => TRUE,
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->setBillingProfile($profile);
    $this->order->save();

    $this->assertCount(0, $this->order->collectAdjustments());
    $order_items = $this->order->getItems();
    $order_item = reset($order_items);
    $this->assertEquals(new Price('10.00', 'USD'), $order_item->getUnitPrice());
    // Confirm that the overridden_unit_price flag is preserved.
    $this->assertTrue($order_item->isUnitPriceOverridden());
  }

}
