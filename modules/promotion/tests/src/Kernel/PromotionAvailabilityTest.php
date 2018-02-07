<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the promotion availability logic.
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
   * Test general availability.
   */
  public function testAvailability() {
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
  }

  /**
   * Tests the start date logic.
   */
  public function testStartDate() {
    // Default start date.
    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    // The computed ->date property always converts dates to UTC,
    // causing failures around 8PM EST once the UTC date passes midnight.
    $now = (new \DateTime())->setTime(20, 00);
    $this->container->get('request_stack')->getCurrentRequest()->server->set('REQUEST_TIME', $now->getTimestamp());
    $this->assertTrue($promotion->available($this->order));

    // Past start date.
    $date = new DrupalDateTime('2017-01-01');
    $promotion->setStartDate($date);
    $this->assertTrue($promotion->available($this->order));

    // Future start date.
    $date = new DrupalDateTime();
    $date->modify('+1 week');
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
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    // Past end date.
    $date = new DrupalDateTime('2017-01-01');
    $promotion->setEndDate($date);
    $this->assertFalse($promotion->available($this->order));

    // Future end date.
    $date = new DrupalDateTime();
    $date->modify('+1 week');
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
      'status' => TRUE,
    ]);
    $promotion->save();
    $this->assertTrue($promotion->available($this->order));

    \Drupal::service('commerce_promotion.usage')->register($this->order, $promotion);
    $this->assertTrue($promotion->available($this->order));
    \Drupal::service('commerce_promotion.usage')->register($this->order, $promotion);
    $this->assertFalse($promotion->available($this->order));
  }

}
