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

/**
 * Tests the order total summary.
 *
 * @group commerce
 */
class OrderTotalSummaryTest extends OrderKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Order total summary.
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
    'commerce_promotion',
    'commerce_test',
    'commerce_order_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');

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
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'state' => 'completed',
    ]);

    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the order total summary with order adjustments.
   */
  public function testWithOrderAdjustments() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $this->order->addItem($order_item);

    $this->order->addAdjustment(new Adjustment([
      'type' => 'promotion',
      'label' => 'Back to school discount',
      'amount' => new Price('-5.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
    ]));
    $this->order->save();

    $totals = $this->orderTotalSummary->buildTotals($this->order);
    $this->assertEquals(new Price('12.00', 'USD'), $totals['subtotal']);
    $this->assertEquals(new Price('7.00', 'USD'), $totals['total']);

    $this->assertCount(1, $totals['adjustments']);
    $first = array_shift($totals['adjustments']);
    $this->assertEquals('promotion', $first['type']);
    $this->assertEquals('Back to school discount', $first['label']);
    $this->assertEquals(new Price('-5', 'USD'), $first['amount']);
    $this->assertEquals('0.1', $first['percentage']);
  }

  /**
   * Tests the order total summary with order item adjustments.
   */
  public function testWithOrderItemAdjustments() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->addAdjustment(new Adjustment([
      'type' => 'promotion',
      'label' => 'Back to school discount',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
    ]));
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $this->order->addItem($order_item);
    $this->order->save();

    $totals = $this->orderTotalSummary->buildTotals($this->order);
    $this->assertEquals(new Price('12.00', 'USD'), $totals['subtotal']);
    $this->assertEquals(new Price('11.00', 'USD'), $totals['total']);

    $this->assertCount(1, $totals['adjustments']);
    $first = array_shift($totals['adjustments']);
    $this->assertEquals('promotion', $first['type']);
    $this->assertEquals('Back to school discount', $first['label']);
    $this->assertEquals(new Price('-1', 'USD'), $first['amount']);
    $this->assertEquals('0.1', $first['percentage']);
  }

  /**
   * Tests the order total summary with both order and order item adjustments.
   */
  public function testWithAllAdjustments() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->addAdjustment(new Adjustment([
      'type' => 'promotion',
      'label' => 'Back to school discount',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
    ]));
    // This adjustment should be first.
    $order_item->addAdjustment(new Adjustment([
      'type' => 'test_adjustment_type',
      'label' => '50 cent item fee',
      'amount' => new Price('0.50', 'USD'),
    ]));
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $this->order->addItem($order_item);

    $this->order->addAdjustment(new Adjustment([
      'type' => 'promotion',
      'label' => 'Back to school discount',
      'amount' => new Price('-5.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
    ]));
    $this->order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
    ]));
    $this->order->save();

    $totals = $this->orderTotalSummary->buildTotals($this->order);
    $this->assertEquals(new Price('24.00', 'USD'), $totals['subtotal']);
    $this->assertEquals(new Price('28.50', 'USD'), $totals['total']);

    $this->assertCount(3, $totals['adjustments']);
    $first = array_shift($totals['adjustments']);
    $this->assertEquals('test_adjustment_type', $first['type']);
    $this->assertEquals('50 cent item fee', $first['label']);
    $this->assertEquals(new Price('0.50', 'USD'), $first['amount']);
    $this->assertNull($first['percentage']);

    $second = array_shift($totals['adjustments']);
    $this->assertEquals('promotion', $second['type']);
    $this->assertEquals('Back to school discount', $second['label']);
    $this->assertEquals(new Price('-6', 'USD'), $second['amount']);
    $this->assertEquals('0.1', $second['percentage']);

    $third = array_shift($totals['adjustments']);
    $this->assertEquals('custom', $third['type']);
    $this->assertEquals('Handling fee', $third['label']);
    $this->assertEquals(new Price('10', 'USD'), $third['amount']);
    $this->assertNull($third['percentage']);
  }

  /**
   * Tests the order total summary with included adjustments.
   */
  public function testIncludedAdjustments() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $this->order->addItem($order_item);

    $this->order->addAdjustment(new Adjustment([
      'type' => 'promotion',
      'label' => 'Back to school discount',
      'amount' => new Price('-5.00', 'USD'),
      'source_id' => '1',
      'included' => TRUE,
    ]));
    $this->order->save();
    $this->order->addAdjustment(new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('2.00', 'USD'),
      'source_id' => 'us_vat|default|reduced',
      'percentage' => '0.2',
      'included' => TRUE,
    ]));
    $this->order->save();

    $totals = $this->orderTotalSummary->buildTotals($this->order);
    $this->assertEquals(new Price('12.00', 'USD'), $totals['subtotal']);
    $this->assertEquals(new Price('12.00', 'USD'), $totals['total']);
    // Confirm that the promotion adjustment was filtered out,
    // but the tax one wasn't.
    $this->assertCount(1, $totals['adjustments']);
    $first = array_shift($totals['adjustments']);
    $this->assertEquals('tax', $first['type']);
    $this->assertEquals('VAT', $first['label']);
    $this->assertEquals(new Price('2.00', 'USD'), $first['amount']);
    $this->assertEquals('us_vat|default|reduced', $first['source_id']);
    $this->assertEquals('0.2', $first['percentage']);
  }

}
