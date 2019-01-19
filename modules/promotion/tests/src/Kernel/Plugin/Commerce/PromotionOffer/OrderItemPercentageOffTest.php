<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the percentage off offer for order items.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPercentageOff
 *
 * @group commerce
 */
class OrderItemPercentageOffTest extends CommerceKernelTestBase {

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'path',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_product',
      'commerce_promotion',
    ]);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '9.99',
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

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

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
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '2',
      'unit_price' => $this->variation->getPrice(),
      'purchased_entity' => $this->variation->id(),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'display_inclusive' => TRUE,
          'percentage' => '0.50',
        ],
      ],
    ]);
    $promotion->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals(new Price('4.99', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('9.98', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('9.98', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals(new Price('-10.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('0.50', $adjustment->getPercentage());
    $this->assertTrue($adjustment->isIncluded());
    $this->order->recalculateTotalPrice();
    $this->assertEquals(new Price('9.98', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests the non-display-inclusive offer.
   *
   * @covers ::apply
   */
  public function testNonDisplayInclusive() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '2',
      'unit_price' => $this->variation->getPrice(),
      'purchased_entity' => $this->variation->id(),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'display_inclusive' => FALSE,
          'percentage' => '0.50',
        ],
      ],
    ]);
    $promotion->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals(new Price('9.99', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('19.98', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('9.99', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $adjustment = $order_item->getAdjustments()[0];
    $this->assertEquals(new Price('-9.99', 'USD'), $adjustment->getAmount());
    $this->assertEquals('0.50', $adjustment->getPercentage());
    $this->assertFalse($adjustment->isIncluded());
    $this->order->recalculateTotalPrice();
    $this->assertEquals(new Price('9.99', 'USD'), $this->order->getTotalPrice());
  }

}
