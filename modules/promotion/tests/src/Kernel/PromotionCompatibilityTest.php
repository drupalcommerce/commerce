<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests promotion compatibility options.
 *
 * @group commerce
 * @group commerce_promotion
 */
class PromotionCompatibilityTest extends OrderKernelTestBase {

  /**
   * The test order.
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
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig(['commerce_promotion']);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'order_items' => [$order_item],
      'total_price' => new Price('100.00', 'USD'),
      'uid' => $this->createUser()->id(),
    ]);
  }

  /**
   * Tests the compatibility setting.
   */
  public function testCompatibility() {
    $order_type = OrderType::load('default');

    // Starts now, enabled. No end time.
    $promotion1 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$order_type],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
    ]);
    $this->assertEquals(SAVED_NEW, $promotion1->save());

    $promotion2 = Promotion::create([
      'name' => 'Promotion 2',
      'order_types' => [$order_type],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
    ]);
    $this->assertEquals(SAVED_NEW, $promotion2->save());

    $this->assertTrue($promotion1->applies($this->order));
    $this->assertTrue($promotion2->applies($this->order));

    $promotion1->setWeight(-10);
    $promotion1->save();

    $promotion2->setWeight(10);
    $promotion2->setCompatibility(PromotionInterface::COMPATIBLE_NONE);
    $promotion2->save();

    $promotion1->apply($this->order);
    $this->assertFalse($promotion2->applies($this->order));

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(1, count($this->order->collectAdjustments()));
  }

}
