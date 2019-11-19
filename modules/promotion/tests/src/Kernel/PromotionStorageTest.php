<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests promotion storage.
 *
 * @group commerce
 */
class PromotionStorageTest extends OrderKernelTestBase {

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_promotion',
  ];

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The test order type.
   *
   * @var \Drupal\commerce_order\Entity\OrderTypeInterface
   */
  protected $orderType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig(['commerce_promotion']);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $this->promotionStorage = $this->container->get('entity_type.manager')->getStorage('commerce_promotion');

    $this->orderType = OrderType::load('default');
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();

    $this->order = Order::create([
      'type' => 'default',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->createUser(),
      'order_items' => [$order_item],
      'state' => 'completed',
      // Used when determining availability, via $order->getCalculationDate().
      'placed' => strtotime('2019-11-15 10:14:00'),
    ]);
  }

  /**
   * Tests loadAvailable().
   */
  public function testLoadAvailable() {
    // Starts now. No end date.
    $promotion1 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-11-15T10:14:00',
      'status' => TRUE,
    ]);
    $promotion1->save();
    $promotion1 = $this->reloadEntity($promotion1);
    // Past start date, no end date.
    $promotion2 = Promotion::create([
      'name' => 'Promotion 2',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion2->save();
    $promotion2 = $this->reloadEntity($promotion2);
    // Past start date, no end date. Disabled.
    $promotion3 = Promotion::create([
      'name' => 'Promotion32',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2014-01-01T00:00:00',
      'status' => FALSE,
    ]);
    $promotion3->save();
    // Past start date, ends now.
    $promotion4 = Promotion::create([
      'name' => 'Promotion 4',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'end_date' => '2019-11-15T10:14:00',
      'status' => TRUE,
    ]);
    $promotion4->save();
    // Past start date, future end date.
    $promotion5 = Promotion::create([
      'name' => 'Promotion 5',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'end_date' => '2020-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion5->save();
    $promotion5 = $this->reloadEntity($promotion5);
    // Past start date, past end date.
    $promotion6 = Promotion::create([
      'name' => 'Promotion 6',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'end_date' => '2019-10-15T10:14:00',
      'status' => TRUE,
    ]);
    $promotion6->save();

    $promotions = $this->promotionStorage->loadAvailable($this->order);
    $this->assertCount(3, $promotions);
    $this->assertEquals([
      $promotion1->id(), $promotion2->id(), $promotion5->id(),
    ], array_keys($promotions));
  }

  /**
   * Tests that promotions with coupons do not get loaded.
   */
  public function testValidWithCoupons() {
    $promotion1 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion1->save();

    /** @var \Drupal\commerce_promotion\Entity\Promotion $promotion2 */
    $promotion2 = Promotion::create([
      'name' => 'Promotion 2',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion2->save();
    // Add a coupon to promotion2 and validate it does not load.
    $coupon = Coupon::create([
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $promotion2->get('coupons')->appendItem($coupon);
    $promotion2->save();
    $promotion2 = $this->reloadEntity($promotion2);

    $promotion3 = Promotion::create([
      'name' => 'Promotion 3',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion3->save();

    $this->assertEquals(2, count($this->promotionStorage->loadAvailable($this->order)));
  }

  /**
   * Tests that promotions are loaded by weight.
   *
   * @group debug
   */
  public function testWeight() {
    $promotion1 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
      'weight' => 4,
    ]);
    $promotion1->save();
    $promotion2 = Promotion::create([
      'name' => 'Promotion 2',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
      'weight' => 2,
    ]);
    $promotion2->save();
    $promotion3 = Promotion::create([
      'name' => 'Promotion 3',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
      'weight' => -10,
    ]);
    $promotion3->save();
    $promotion4 = Promotion::create([
      'name' => 'Promotion 4',
      'order_types' => [$this->orderType],
      'stores' => [$this->store->id()],
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion4->save();

    $promotions = $this->promotionStorage->loadAvailable($this->order);
    $promotion = array_shift($promotions);
    $this->assertEquals($promotion3->label(), $promotion->label());
    $promotion = array_shift($promotions);
    $this->assertEquals($promotion4->label(), $promotion->label());
    $promotion = array_shift($promotions);
    $this->assertEquals($promotion2->label(), $promotion->label());
    $promotion = array_shift($promotions);
    $this->assertEquals($promotion1->label(), $promotion->label());
  }

}
