<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the promotion order processor.
 *
 * @group commerce
 */
class PromotionOrderProcessorTest extends OrderKernelTestBase {

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

    $this->user = $this->createUser();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the order amount condition.
   */
  public function testOrderTotal() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
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
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $promotion->save();

    $this->assertTrue($promotion->applies($this->order));
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();

    $this->assertEquals(1, count($this->order->collectAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests the coupon based promotion processor.
   */
  public function testCouponPromotion() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();

    // Starts now, enabled. No end time.
    $promotion_with_coupon = Promotion::create([
      'name' => 'Promotion (with coupon)',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion_with_coupon->save();

    $coupon = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion_with_coupon->get('coupons')->appendItem($coupon);
    $promotion_with_coupon->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);

    $this->assertEquals(0, count($this->order->getAdjustments()));

    $this->order->get('coupons')->appendItem($coupon);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();

    $this->assertEquals(1, count($this->order->collectAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());

    $coupon->setEnabled(FALSE);
    $coupon->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order->recalculateTotalPrice();

    $this->assertEquals(0, count($this->order->collectAdjustments()));
  }

  /**
   * Tests the order refresh to remove coupons from an order when invalid.
   */
  public function testCouponRemoval() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->createUser(),
      'order_items' => [$order_item],
    ]);
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();

    $promotion = Promotion::create([
      'name' => 'Promotion',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
    ]);
    $promotion->save();

    $coupon = Coupon::create([
      'promotion_id' => $promotion->id(),
      'code' => 'coupon_code',
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $coupon->save();

    $order->get('coupons')->appendItem($coupon);

    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $this->assertCount(1, $order->get('coupons')->getValue());

    $coupon->setEnabled(FALSE);
    $coupon->save();

    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $this->assertCount(0, $order->get('coupons')->getValue());

    $coupon->setEnabled(TRUE);
    $coupon->save();
    $order->get('coupons')->appendItem($coupon);

    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $this->assertCount(1, $order->get('coupons')->getValue());

    $coupon->delete();

    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $this->assertCount(0, $order->get('coupons')->getValue());
  }

}
