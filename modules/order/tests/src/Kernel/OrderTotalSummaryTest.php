<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the order total summary service.
 *
 * @group commerce
 */
class OrderTotalSummaryTest extends CommerceKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Order total summary service.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_test',
    'commerce_order_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    $this->orderTotalSummary = $this->container->get('commerce_order.order_total_summary');

    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();
    $product->addVariation($variation1)->save();

    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
    ]);
    $order->save();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $order_item->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => '1 dollar off',
      'amount' => new Price('-1.00', 'USD'),
    ]));
    $order_item->save();

    // This adjustment should be first.
    $order_item->addAdjustment(new Adjustment([
      'type' => 'test_adjustment_type',
      'label' => '50 cent item fee',
      'amount' => new Price('0.50', 'USD'),
    ]));

    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $order->addItem($order_item);

    // Add order adjustments.
    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => '5 dollars off',
      'amount' => new Price('-5.00', 'USD'),
    ]));

    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
    ]));

    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the order total summary.
   */
  public function testOrderTotalSummary() {
    $totals = $this->orderTotalSummary->buildTotals($this->order);

    $this->assertEquals(new Price('24.00', 'USD'), $totals['subtotal']);
    // Total is correctly calculated!
    $this->assertEquals(new Price('28.00', 'USD'), $totals['total']);
    $this->assertArrayHasKey('custom', $totals['adjustments']);
    // But these type of adjustments are not listed!
    $this->assertArrayHasKey('test_adjustment_type', $totals['adjustments']);
    $this->assertCount(1, $totals['adjustments']['test_adjustment_type']['items']);
    $this->assertCount(2, $totals['adjustments']['custom']['items']);
    $this->assertEquals('50 cent item fee', $totals['adjustments']['test_adjustment_type']['items'][0]['label']);
    $this->assertEquals(new Price('1.00', 'USD'), $totals['adjustments']['test_adjustment_type']['items'][0]['amount']);
    $this->assertEquals('1 dollar off', $totals['adjustments']['custom']['items'][0]['label']);
    $this->assertEquals(new Price('2.00', 'USD'), $totals['adjustments']['custom']['items'][0]['amount']);
    $this->assertEquals('5 dollars off', $totals['adjustments']['custom']['items'][1]['label']);
    $this->assertEquals(new Price('5.00', 'USD'), $totals['adjustments']['custom']['items'][1]['amount']);
    $this->assertEquals('Handling fee', $totals['adjustments']['custom']['items'][2]['label']);
    $this->assertEquals(new Price('10.00', 'USD'), $totals['adjustments']['custom']['items'][2]['amount']);
  }

}
