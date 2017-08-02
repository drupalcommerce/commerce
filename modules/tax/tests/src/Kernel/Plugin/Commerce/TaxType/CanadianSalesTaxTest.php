<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\CanadianSalesTax;
use Drupal\commerce_tax\TaxableType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\CanadianSalesTax
 * @group commerce
 */
class CanadianSalesTaxTest extends CommerceKernelTestBase {

  /**
   * The tax type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface
   */
  protected $plugin;

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

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

    $configuration = [
      '_entity_id' => 'canadian_sales_tax',
      'display_inclusive' => FALSE,
    ];
    $this->plugin = CanadianSalesTax::create($this->container, $configuration, 'canadian_sales_tax', ['label' => 'Canadian Sales Tax']);
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    // British Columbia customer, GST+PST.
    $order = $this->buildOrder('CA', 'BC');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
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
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('canadian_sales_tax|ca|gst', $adjustment->getSourceId());

    // Ontario customer, HST.
    $order = $this->buildOrder('CA', 'ON');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('canadian_sales_tax|on|hst', $adjustment->getSourceId());

    // US customer.
    $order = $this->buildOrder('US', 'SC');
    $this->plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());
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
