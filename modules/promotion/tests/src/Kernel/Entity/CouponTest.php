<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Coupon entity.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Entity\Coupon
 *
 * @group commerce
 */
class CouponTest extends CommerceKernelTestBase {

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
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * @covers ::getPromotion
   * @covers ::getPromotionId
   * @covers ::getCode
   * @covers ::setCode
   * @covers ::getUsageLimit
   * @covers ::setUsageLimit
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testCoupon() {
    $promotion = Promotion::create([
      'status' => FALSE,
    ]);
    $promotion->save();
    $promotion = $this->reloadEntity($promotion);

    $coupon = Coupon::create([
      'status' => FALSE,
      'promotion_id' => $promotion->id(),
    ]);

    $this->assertEquals($promotion, $coupon->getPromotion());
    $this->assertEquals($promotion->id(), $coupon->getPromotionId());

    $coupon->setCode('test_code');
    $this->assertEquals('test_code', $coupon->getCode());

    $coupon->setUsageLimit(10);
    $this->assertEquals(10, $coupon->getUsageLimit());

    $coupon->setEnabled(TRUE);
    $this->assertEquals(TRUE, $coupon->isEnabled());
  }

  /**
   * @covers ::available
   */
  public function testAvailability() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
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
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();

    $coupon = Coupon::create([
      'promotion_id' => $promotion->id(),
      'code' => 'coupon_code',
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $coupon->save();
    $this->assertTrue($coupon->available($order));

    $coupon->setEnabled(FALSE);
    $this->assertFalse($coupon->available($order));
    $coupon->setEnabled(TRUE);

    $this->container->get('commerce_promotion.usage')->register($order, $promotion, $coupon);
    $this->assertFalse($coupon->available($order));
  }

}
