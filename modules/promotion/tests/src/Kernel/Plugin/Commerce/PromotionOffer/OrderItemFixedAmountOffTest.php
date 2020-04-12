<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the percentage off offer for order items.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemFixedAmountOff
 *
 * @group commerce
 */
class OrderItemFixedAmountOffTest extends OrderKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installConfig(['commerce_promotion']);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $this->variation = $variation;

    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
    ]);
    $product->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->createUser(),
      'store_id' => $this->store,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the display-inclusive offer.
   *
   * @covers ::apply
   */
  public function testDisplayInclusive() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_item_fixed_amount_off',
        'target_plugin_configuration' => [
          'display_inclusive' => TRUE,
          'amount' => [
            'number' => '15.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'status' => TRUE,
    ]);
    $promotion->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '2',
      'unit_price' => $this->variation->getPrice(),
      'purchased_entity' => $this->variation->id(),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    // Offer amount larger than the order item unit price.
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals('Discount', $adjustment->getLabel());
    $this->assertEquals(new Price('-20.00', 'USD'), $adjustment->getAmount());
    $this->assertTrue($adjustment->isIncluded());

    // Offer amount smaller than the order item unit price.
    $this->variation->setPrice(new Price('20', 'USD'));
    $this->variation->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);
    $this->assertEquals(new Price('5.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('10.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('10.00', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals('Discount', $adjustment->getLabel());
    $this->assertEquals(new Price('-30.00', 'USD'), $adjustment->getAmount());
    $this->assertTrue($adjustment->isIncluded());

    // Test with multiple promotions.
    // Starts now, enabled. No end time.
    $another_promotion = Promotion::create([
      'name' => 'Promotion 2',
      'display_name' => '$20 off',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_fixed_amount_off',
        'target_plugin_configuration' => [
          'display_inclusive' => TRUE,
          'amount' => [
            'number' => '20.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
    ]);
    $another_promotion->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);
    $adjustments = $order_item->getAdjustments();
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(2, count($adjustments));
    $this->assertEquals('Discount', $adjustments[0]->getLabel());
    $this->assertEquals(new Price('-30.00', 'USD'), $adjustments[0]->getAmount());
    $this->assertTrue($adjustments[0]->isIncluded());
    $this->assertEquals('$20 off', $adjustments[1]->getLabel());
    $this->assertEquals(new Price('-10.00', 'USD'), $adjustments[1]->getAmount());
    $this->assertTrue($adjustments[1]->isIncluded());
  }

  /**
   * Tests the non-display-inclusive offer.
   *
   * @covers ::apply
   */
  public function testNonDisplayInclusive() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'display_name' => '$15 off',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_fixed_amount_off',
        'target_plugin_configuration' => [
          'display_inclusive' => FALSE,
          'amount' => [
            'number' => '15.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
    ]);
    $promotion->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '2',
      'unit_price' => $this->variation->getPrice(),
      'purchased_entity' => $this->variation->id(),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    // Offer amount larger than the order item unit price.
    $this->assertEquals(new Price('10.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('20.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('0.00', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals('$15 off', $adjustment->getLabel());
    $this->assertEquals(new Price('-20.00', 'USD'), $adjustment->getAmount());
    $this->assertFalse($adjustment->isIncluded());

    // Offer amount smaller than the order item unit price.
    $this->variation->setPrice(new Price('20', 'USD'));
    $this->variation->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $this->assertEquals(new Price('20.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('40.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('10.00', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals('$15 off', $adjustment->getLabel());
    $this->assertEquals(new Price('-30.00', 'USD'), $adjustment->getAmount());
    $this->assertFalse($adjustment->isIncluded());

    // Test with multiple promotions.
    // Starts now, enabled. No end time.
    $another_promotion = Promotion::create([
      'name' => 'Promotion 2',
      'display_name' => '$20 off',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_fixed_amount_off',
        'target_plugin_configuration' => [
          'display_inclusive' => FALSE,
          'amount' => [
            'number' => '20.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
    ]);
    $another_promotion->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);
    $adjustments = $order_item->getAdjustments();
    $this->assertEquals(2, count($adjustments));
    $this->assertEquals(new Price('20.00', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('40.00', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('0', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals('$15 off', $adjustments[0]->getLabel());
    $this->assertEquals(new Price('-30.00', 'USD'), $adjustments[0]->getAmount());
    $this->assertEquals(new Price('-10.00', 'USD'), $adjustments[1]->getAmount());
    $this->assertFalse($adjustments[0]->isIncluded());
    $this->assertFalse($adjustments[1]->isIncluded());
  }

}
