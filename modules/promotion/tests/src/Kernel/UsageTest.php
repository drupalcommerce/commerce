<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the usage tracking of promotions.
 *
 * @group commerce
 * @coversDefaultClass \Drupal\commerce_promotion\PromotionUsage
 */
class UsageTest extends CommerceKernelTestBase {

  /**
   * The coupon storage.
   *
   * @var \Drupal\commerce_promotion\CouponStorageInterface
   */
  protected $couponStorage;

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * The usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
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
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $this->couponStorage = $this->container->get('entity_type.manager')->getStorage('commerce_promotion_coupon');
    $this->promotionStorage = $this->container->get('entity_type.manager')->getStorage('commerce_promotion');
    $this->usage = $this->container->get('commerce_promotion.usage');

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->createUser(),
      'order_items' => [$order_item],
    ]);
  }

  /**
   * Tests the usage API.
   *
   * @covers ::register
   * @covers ::delete
   * @covers ::deleteByCoupon
   * @covers ::load
   * @covers ::loadByCoupon
   * @covers ::loadMultiple
   * @covers ::loadMultipleByCoupon
   */
  public function testUsage() {
    $promotion = $this->prophesize(PromotionInterface::class);
    $promotion->id()->willReturn('100');
    $promotion = $promotion->reveal();
    $coupon = $this->prophesize(CouponInterface::class);
    $coupon->id()->willReturn('4');
    $coupon->getPromotionId()->willReturn('3');
    $coupon = $coupon->reveal();
    $order = $this->prophesize(OrderInterface::class);
    $order->id()->willReturn('1');
    $order->getEmail()->willReturn('admin@example.com');
    $order = $order->reveal();
    $another_order = $this->prophesize(OrderInterface::class);
    $another_order->id()->willReturn('2');
    $another_order->getEmail()->willReturn('customer@example.com');
    $another_order = $another_order->reveal();

    $this->usage->register($order, $promotion);
    $this->assertEquals(1, $this->usage->load($promotion));
    $this->usage->register($another_order, $promotion);
    $this->assertEquals(2, $this->usage->load($promotion));
    // Test filtering by coupon.
    $this->usage->register($order, $promotion, $coupon);
    $this->assertEquals(1, $this->usage->loadByCoupon($coupon));
    $this->assertEquals(3, $this->usage->load($promotion));
    // Test filtering by customer email.
    $this->assertEquals(1, $this->usage->loadByCoupon($coupon, 'admin@example.com'));
    $this->assertEquals(0, $this->usage->loadByCoupon($coupon, 'customer@example.com'));
    $this->assertEquals(2, $this->usage->load($promotion, 'admin@example.com'));
    $this->assertEquals(1, $this->usage->load($promotion, 'customer@example.com'));

    $this->usage->deleteByCoupon([$coupon]);
    $this->assertEquals(0, $this->usage->loadByCoupon($coupon));
    $this->assertEquals(2, $this->usage->load($promotion));

    $this->usage->delete([$promotion]);
    $this->assertEquals(0, $this->usage->load($promotion));
  }

  /**
   * Tests the order integration.
   *
   * @covers ::register
   * @covers ::delete
   * @covers ::deleteByCoupon
   * @covers ::load
   * @covers ::loadMultiple
   */
  public function testOrderIntegration() {
    $first_promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $first_promotion->save();
    $coupon = Coupon::create([
      'code' => $this->randomMachineName(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $second_promotion = $first_promotion->createDuplicate();
    $second_promotion->addCoupon($coupon);
    $second_promotion->save();

    $this->order->get('coupons')->appendItem($coupon);
    $this->order->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(2, count($this->order->getAdjustments()));
    $this->order->save();

    $this->order->getState()->applyTransition($this->order->getState()->getTransitions()['place']);
    $this->order->save();
    $this->assertEquals(1, $this->usage->load($first_promotion));
    $this->assertEquals(1, $this->usage->load($second_promotion));
    $this->assertEquals([1 => 1, 2 => 1], $this->usage->loadMultiple([$first_promotion, $second_promotion]));

    // Deleting a coupon should delete its usage.
    $second_promotion->delete();
    $this->assertEquals(0, $this->usage->load($second_promotion));

    // Deleting a promotion should delete its usage.
    $first_promotion->delete();
    $this->assertEquals(0, $this->usage->load($first_promotion));
    $this->assertEquals([1 => 0, 2 => 0], $this->usage->loadMultiple([$first_promotion, $second_promotion]));
  }

  /**
   * Tests the filtering of promotions past their usage limit.
   */
  public function testPromotionFiltering() {
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'usage_limit' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();

    $this->assertTrue($promotion->applies($this->order));
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->order->save();

    $this->order->getState()->applyTransition($this->order->getState()->getTransitions()['place']);
    $this->order->save();
    $usage = $this->usage->load($promotion);
    $this->assertEquals(1, $usage);

    $order_type = OrderType::load($this->order->bundle());
    $valid_promotions = $this->promotionStorage->loadAvailable($this->order);
    $this->assertEmpty($valid_promotions);
  }

}
