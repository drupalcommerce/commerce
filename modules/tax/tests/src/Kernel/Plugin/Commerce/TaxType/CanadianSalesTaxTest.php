<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\commerce_tax\TaxableType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\CanadianSalesTax
 * @group commerce
 */
class CanadianSalesTaxTest extends OrderKernelTestBase {

  /**
   * The tax type.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $taxType;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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

    // Order item types that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test_physical',
      'label' => 'Test (Physical)',
      'orderType' => 'default',
      'third_party_settings' => [
        'commerce_tax' => ['taxable_type' => TaxableType::PHYSICAL_GOODS],
      ],
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->taxType = TaxType::create([
      'id' => 'canadian_sales_tax',
      'label' => 'Canadian Sales Tax',
      'plugin' => 'canadian_sales_tax',
      'configuration' => [
        'display_inclusive' => FALSE,
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
    // British Columbia customer, GST+PST.
    $order = $this->buildOrder('CA', 'BC');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $this->assertCount(2, $adjustments);
    $this->assertEquals('canadian_sales_tax|ca|gst', $adjustments[0]->getSourceId());
    $this->assertEquals('canadian_sales_tax|bc|pst', $adjustments[1]->getSourceId());
    $this->assertEquals(t('GST'), $adjustments[0]->getLabel());
    $this->assertEquals(t('PST'), $adjustments[1]->getLabel());
    // Both taxes should be calculated on the unit price (non-cumulative).
    $this->assertEquals(new Price('0.5', 'USD'), $adjustments[0]->getAmount());
    $this->assertEquals(new Price('0.7', 'USD'), $adjustments[1]->getAmount());

    // Alberta customer, GST.
    $order = $this->buildOrder('CA', 'AB');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('canadian_sales_tax|ca|gst', $adjustment->getSourceId());

    // Ontario customer, HST.
    $order = $this->buildOrder('CA', 'ON');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('canadian_sales_tax|on|hst', $adjustment->getSourceId());

    // US customer.
    $order = $this->buildOrder('US', 'SC');
    $plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());
  }

  /**
   * @covers ::getZones
   */
  public function testGetZones() {
    /** @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface $plugin */
    $plugin = $this->taxType->getPlugin();
    $zones = $plugin->getZones();
    $this->assertArrayHasKey('ca', $zones);
    $this->assertArrayHasKey('bc', $zones);
    $this->assertArrayHasKey('mb', $zones);
    $this->assertArrayHasKey('nb', $zones);
  }

  /**
   * Builds an order for testing purposes.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  protected function buildOrder($customer_country, $customer_province) {
    $store = Store::create([
      'type' => 'default',
      'label' => 'My store',
      'address' => [
        'country_code' => 'CA',
      ],
      'prices_include_tax' => FALSE,
    ]);
    $store->save();
    $customer_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => $customer_country,
        'administrative_area' => $customer_province,
      ],
    ]);
    $customer_profile->save();
    $order_item = OrderItem::create([
      'type' => 'test_physical',
      'quantity' => 1,
      'unit_price' => new Price('10.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'uid' => $this->user->id(),
      'store_id' => $store,
      'billing_profile' => $customer_profile,
      'order_items' => [$order_item],
    ]);
    $order->save();

    return $order;
  }

}
