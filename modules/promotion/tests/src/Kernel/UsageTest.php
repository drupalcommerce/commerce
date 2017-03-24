<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the usage tracking of promotions.
 *
 * @group commerce
 * @group commerce_promotion
 *
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
    $this->installEntitySchema('commerce_order_type');
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

    $this->user = $this->createUser();

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

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
   * Manually test the add/get of usages.
   *
   * @covers ::addUsage
   * @covers ::getUsage
   */
  public function testMockUsage() {
    $promotion = $this->prophesize(PromotionInterface::class);
    $promotion->id()->wilLReturn('3');
    $coupon = $this->prophesize(CouponInterface::class);
    $coupon->id()->willReturn('4');
    $coupon->getPromotionId()->willReturn('3');
    $order = $this->prophesize(OrderInterface::class);
    $order->id()->willReturn('12345');
    $order->getCustomerId()->willReturn('2');

    $this->usage->addUsage($order->reveal(), $promotion->reveal());
    $this->assertEquals(1, $this->usage->getUsage($promotion->reveal()));

    // Claiming coupon usage for a match promotion entry should merge.
    $this->usage->addUsage($order->reveal(), $promotion->reveal(), $coupon->reveal());
    $this->assertEquals(1, $this->usage->getUsage($promotion->reveal(), $coupon->reveal()));
    $this->assertEquals(1, $this->usage->getUsage($promotion->reveal()));
  }

  /**
   * Tests promotion usage.
   *
   * @covers ::addUsage
   * @covers ::getUsage
   */
  public function testPromotionUsage() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
    ]);
    $promotion->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->order->save();

    $this->order->getState()->applyTransition($this->order->getState()->getTransitions()['place']);
    $this->order->save();

    $usage = $this->usage->getUsage($promotion);
    $this->assertEquals(1, $usage);
  }

  /**
   * Tests coupon usage.
   *
   * @covers ::addUsage
   * @covers ::getUsage
   */
  public function testCouponUsage() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
    ]);
    $promotion->save();
    $coupon_code = $this->randomMachineName();
    $coupon = Coupon::create([
      'code' => $coupon_code,
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion->get('coupons')->appendItem($coupon);
    $promotion->save();

    $this->order->get('coupons')->appendItem($coupon);
    $this->order->save();

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->order->save();

    $this->order->getState()->applyTransition($this->order->getState()->getTransitions()['place']);
    $this->order->save();

    $usage = $this->usage->getUsage($promotion, $coupon);
    $this->assertEquals(1, $usage);
    $usage = $this->usage->getUsage($promotion);
    $this->assertEquals(1, $usage);
  }

  /**
   * Tests that a promotion is no longer valid after its usage limit.
   */
  public function testPromotionIsNotValidAfterTooManyUses() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'usage_limit' => 1,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
    ]);
    $promotion->save();

    $this->assertTrue($promotion->applies($this->order));

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->order->save();

    $this->order->getState()->applyTransition($this->order->getState()->getTransitions()['place']);
    $this->order->save();

    $usage = $this->usage->getUsage($promotion);
    $this->assertEquals(1, $usage);

    $order_type = OrderType::load($this->order->bundle());
    $valid_promotions = $this->promotionStorage->loadValid($order_type, $this->store);
    $this->assertEmpty($valid_promotions);
  }

}
