<?php

namespace Drupal\Tests\commerce_tax\Kernel\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\EuropeanUnionVat;
use Drupal\commerce_tax\TaxableType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\EuropeanUnionVat
 * @group commerce
 */
class EuropeanUnionVatTest extends CommerceKernelTestBase {

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
    OrderItemType::create([
      'id' => 'test_digital',
      'label' => 'Test (Digital)',
      'orderType' => 'default',
      'third_party_settings' => [
        'commerce_tax' => ['taxable_type' => TaxableType::DIGITAL_GOODS],
      ],
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $configuration = [
      '_entity_id' => 'european_union_vat',
      'display_inclusive' => TRUE,
    ];
    $this->plugin = EuropeanUnionVat::create($this->container, $configuration, 'european_union_vat', ['label' => 'EU VAT']);
  }

  /**
   * @covers ::applies
   * @covers ::apply
   */
  public function testApplication() {
    // German customer, French store, VAT number provided.
    // French customer, French store, VAT number provided.
    // @todo

    // German customer, French store, physical product.
    $order = $this->buildOrder('DE', 'FR');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|fr|standard', $adjustment->getSourceId());

    // German customer, French store registered for German VAT, physical product.
    $order = $this->buildOrder('DE', 'FR', ['DE']);
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer, French store, digital product before Jan 1st 2015.
    $order = $this->buildOrder('DE', 'FR', [], TRUE);
    $order->setPlacedTime(mktime(1, 1, 1, 1, 1, 2013));
    $order->save();
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|fr|standard', $adjustment->getSourceId());

    // German customer, French store, digital product.
    $order = $this->buildOrder('DE', 'FR', [], TRUE);
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer, US store registered in FR, digital product.
    $order = $this->buildOrder('DE', 'US', ['FR'], TRUE);
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer with VAT number, US store registered in FR, digital product.
    // @todo

    // Serbian customer, French store, physical product.
    $order = $this->buildOrder('RS', 'FR');
    $this->assertTrue($this->plugin->applies($order));
    $this->plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());

    // French customer, Serbian store, physical product.
    $order = $this->buildOrder('FR', 'RS');
    $this->assertFalse($this->plugin->applies($order));
  }

  /**
   * Builds an order for testing purposes.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  protected function buildOrder($customer_country, $store_country, array $store_registrations = [], $digital = FALSE) {
    $store = Store::create([
      'type' => 'default',
      'label' => 'My store',
      'address' => [
        'country_code' => $store_country,
      ],
      'prices_include_tax' => FALSE,
      'tax_registrations' => $store_registrations,
    ]);
    $store->save();
    $customer_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => $customer_country,
      ],
    ]);
    $customer_profile->save();
    $order_item = OrderItem::create([
      'type' => $digital ? 'test_digital' : 'test_physical',
      'quantity' => 1,
      // Intentionally uneven number, to test rounding.
      'unit_price' => new Price('10.33', 'USD'),
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
