<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the promotion availability logic.
 *
 * @group commerce
 */
class PromotionAvailabilityTest extends OrderKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
    $this->installConfig(['commerce_promotion']);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
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
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Test general availability.
   */
  public function testAvailability() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 2,
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    $promotion->setEnabled(FALSE);
    $this->assertFalse($promotion->available($this->order));
    $promotion->setEnabled(TRUE);

    $promotion->setOrderTypeIds(['test']);
    $this->assertFalse($promotion->available($this->order));
    $promotion->setOrderTypeIds(['default']);

    $promotion->setStoreIds(['90']);
    $this->assertFalse($promotion->available($this->order));
    $promotion->setStoreIds([$this->store->id()]);
  }

  /**
   * Tests the start date logic.
   */
  public function testStartDate() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $promotion->save();

    // Start date equal to the order placed date.
    $date = new DrupalDateTime('2019-11-15 10:14:00');
    $promotion->setStartDate($date);
    $this->assertTrue($promotion->available($this->order));

    // Past start date.
    $date = new DrupalDateTime('2019-11-10 10:14:00');
    $promotion->setStartDate($date);
    $this->assertTrue($promotion->available($this->order));

    // Future start date.
    $date = new DrupalDateTime('2019-11-20 10:14:00');
    $promotion->setStartDate($date);
    $this->assertFalse($promotion->available($this->order));
  }

  /**
   * Tests the end date logic.
   */
  public function testEndDate() {
    // No end date date.
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    // End date equal to the order placed date.
    $date = new DrupalDateTime('2019-11-15 10:14:00');
    $promotion->setEndDate($date);
    $this->assertFalse($promotion->available($this->order));

    // Past end date.
    $date = new DrupalDateTime('2017-01-01 00:00:00');
    $promotion->setEndDate($date);
    $this->assertFalse($promotion->available($this->order));

    // Future end date.
    $date = new DrupalDateTime('2019-11-20 10:14:00');
    $promotion->setEndDate($date);
    $this->assertTrue($promotion->available($this->order));
  }

  /**
   * Tests the usage count logic.
   */
  public function testUsageCount() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 2,
      'start_date' => '2019-01-01T00:00:00',
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    $this->container->get('commerce_promotion.usage')->register($this->order, $promotion);
    $this->assertTrue($promotion->available($this->order));
    $this->container->get('commerce_promotion.usage')->register($this->order, $promotion);
    $this->assertFalse($promotion->available($this->order));
  }

}
