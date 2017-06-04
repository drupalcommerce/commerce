<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Promotion entity.
 *
 * @group commerce
 */
class PromotionAvailabilityTest extends CommerceKernelTestBase {

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
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests a promotion is available immediately with default start date.
   */
  public function testDefaultStartDate() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    // The default start date fails around midnight due to core's handling
    // of computed date field values.
    // @see property once https://www.drupal.org/node/2632040
    $now = (new \DateTime())->setTime(20, 00);
    $this->container->get('request_stack')->getCurrentRequest()->server->set('REQUEST_TIME', $now->getTimestamp());
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));
  }

  /**
   * Tests a promotion is available with an early start date.
   */
  public function testEarlierStartDate() {
    $this->testDefaultStartDate();
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));
  }

  /**
   * Tests a promotion is not available with a later start date.
   */
  public function testLaterStartDate() {
    $next_week = new DrupalDateTime();
    $next_week->modify('+1 week');
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'start_date' => $next_week->format(DATETIME_DATE_STORAGE_FORMAT),
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertFalse($promotion->available($this->order));
  }

  /**
   * Tests the usage count wrapped within ::available.
   */
  public function testUsageCount() {
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 2,
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    \Drupal::service('commerce_promotion.usage')->addUsage($this->order, $promotion);
    $this->assertTrue($promotion->available($this->order));
    \Drupal::service('commerce_promotion.usage')->addUsage($this->order, $promotion);
    $this->assertFalse($promotion->available($this->order));
  }

  /**
   * Test all of the availability items.
   */
  public function testAvailability() {
    $original_time_service = $this->container->get('datetime.time');

    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 2,
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

    $fake_time = $this->prophesize(TimeInterface::class);
    $fake_time->getRequestTime()->willReturn(mktime(0, 0, 0, '01', '15', '2016'));
    $this->container->set('datetime.time', $fake_time->reveal());
    $this->assertFalse($promotion->available($this->order));

    $fake_time = $this->prophesize(TimeInterface::class);
    $fake_time->getRequestTime()->willReturn(mktime(0, 0, 0, '01', '15', '2017'));
    $this->container->set('datetime.time', $fake_time->reveal());
    $promotion->setEndDate(new DrupalDateTime('2017-01-14'));
    $this->assertFalse($promotion->available($this->order));
    $promotion->setEndDate(NULL);
    $this->container->set('datetime.time', $original_time_service);

    \Drupal::service('commerce_promotion.usage')->addUsage($this->order, $promotion);
    $this->assertTrue($promotion->available($this->order));
    \Drupal::service('commerce_promotion.usage')->addUsage($this->order, $promotion);
    $this->assertFalse($promotion->available($this->order));
  }

}
