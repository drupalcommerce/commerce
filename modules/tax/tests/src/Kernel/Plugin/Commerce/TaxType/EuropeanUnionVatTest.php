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
 * @coversDefaultClass \Drupal\commerce_tax\Plugin\Commerce\TaxType\EuropeanUnionVat
 * @group commerce
 */
class EuropeanUnionVatTest extends OrderKernelTestBase {

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

    $this->installConfig('commerce_tax');

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

    $this->taxType = TaxType::create([
      'id' => 'european_union_vat',
      'label' => 'EU VAT',
      'plugin' => 'european_union_vat',
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
    // German customer, French store, VAT number provided.
    $order = $this->buildOrder('DE', 'FR', 'DE123456789');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|ic|zero', $adjustment->getSourceId());

    // French customer, French store, VAT number provided.
    $order = $this->buildOrder('FR', 'FR', 'FR00123456789');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|fr|standard', $adjustment->getSourceId());

    // German customer, French store, physical product.
    $order = $this->buildOrder('DE', 'FR');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|fr|standard', $adjustment->getSourceId());

    // German customer, French store registered for German VAT, physical product.
    $order = $this->buildOrder('DE', 'FR', '', ['DE']);
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer, French store, digital product before Jan 1st 2015.
    $order = $this->buildOrder('DE', 'FR', '', [], TRUE);
    $order->setPlacedTime(mktime(1, 1, 1, 1, 1, 2014));
    $order->save();
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|fr|standard', $adjustment->getSourceId());

    // German customer, French store, digital product.
    $order = $this->buildOrder('DE', 'FR', '', [], TRUE);
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer, US store registered in FR, digital product.
    $order = $this->buildOrder('DE', 'US', '', ['FR'], TRUE);
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|de|standard', $adjustment->getSourceId());

    // German customer with VAT number, US store registered in FR, digital product.
    $order = $this->buildOrder('DE', 'US', 'DE123456789', ['FR'], TRUE);
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $adjustments = $order->collectAdjustments();
    $adjustment = reset($adjustments);
    $this->assertCount(1, $adjustments);
    $this->assertEquals('european_union_vat|ic|zero', $adjustment->getSourceId());

    // Serbian customer, French store, physical product.
    $order = $this->buildOrder('RS', 'FR');
    $this->assertTrue($plugin->applies($order));
    $plugin->apply($order);
    $this->assertCount(0, $order->collectAdjustments());

    // French customer, Serbian store, physical product.
    $order = $this->buildOrder('FR', 'RS');
    $this->assertFalse($plugin->applies($order));
  }

  /**
   * Builds an order for testing purposes.
   *
   * @param string $customer_country
   *   The customer's country code.
   * @param string $store_country
   *   The store's country code.
   * @param string $tax_number
   *   The customer's tax number.
   * @param array $store_registrations
   *   The store's tax registrations.
   * @param bool $digital
   *   Whether the order will be used for a digital product.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  protected function buildOrder($customer_country, $store_country, $tax_number = '', array $store_registrations = [], $digital = FALSE) {
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
    if ($tax_number) {
      $customer_profile->set('tax_number', [
        'type' => 'european_union_vat',
        'value' => $tax_number,
        'verification_state' => 'success',
      ]);
    }
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
