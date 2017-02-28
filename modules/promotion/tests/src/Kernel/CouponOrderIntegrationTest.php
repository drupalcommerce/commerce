<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests coupon integration with orders.
 *
 * @group commerce
 */
class CouponOrderIntegrationTest extends CommerceKernelTestBase {

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
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

    $this->user = $this->createUser();

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
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the coupons field added to orders.
   */
  public function testOrderCouponField() {
    $coupon1 = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon1->save();
    $coupon2 = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon2->save();

    $this->order->get('coupons')->appendItem($coupon1);
    $this->order->get('coupons')->appendItem($coupon2);
    $this->order->save();

    $this->assertSame($coupon2, $this->order->get('coupons')->offsetGet(1)->entity);

  }

}
