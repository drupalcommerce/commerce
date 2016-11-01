<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the Order entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\Order
 *
 * @group commerce
 */
class OrderTest extends EntityKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A sample store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'entity',
    'entity_reference_revisions',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_store');
    $this->installConfig('commerce_order');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
      'default_currency' => 'USD',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);
  }

  /**
   * Tests the order entity and its methods.
   *
   * @covers ::getOrderNumber
   * @covers ::setOrderNumber
   * @covers ::getStore
   * @covers ::setStore
   * @covers ::getStoreId
   * @covers ::setStoreId
   * @covers ::getCustomer
   * @covers ::setCustomer
   * @covers ::getCustomerId
   * @covers ::setCustomerId
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getIpAddress
   * @covers ::setIpAddress
   * @covers ::getBillingProfile
   * @covers ::setBillingProfile
   * @covers ::getItems
   * @covers ::setItems
   * @covers ::hasItems
   * @covers ::addItem
   * @covers ::removeItem
   * @covers ::hasItem
   * @covers ::getAdjustments
   * @covers ::setAdjustments
   * @covers ::addAdjustment
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   * @covers ::getState
   * @covers ::getRefreshState
   * @covers ::setRefreshState
   * @covers ::getData
   * @covers ::setData
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getPlacedTime
   * @covers ::setPlacedTime
   * @covers ::getCompletedTime
   * @covers ::setCompletedTime
   */
  public function testOrder() {
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $another_order_item */
    $another_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('3.00', 'USD'),
    ]);
    $another_order_item->save();
    $another_order_item = $this->reloadEntity($another_order_item);

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->save();

    $order->setOrderNumber(7);
    $this->assertEquals(7, $order->getOrderNumber());

    $order->setStore($this->store);
    $this->assertEquals($this->store, $order->getStore());
    $this->assertEquals($this->store->id(), $order->getStoreId());
    $order->setStoreId(0);
    $this->assertEquals(NULL, $order->getStore());
    $order->setStoreId([$this->store->id()]);
    $this->assertEquals($this->store, $order->getStore());
    $this->assertEquals($this->store->id(), $order->getStoreId());

    $order->setCustomer($this->user);
    $this->assertEquals($this->user, $order->getCustomer());
    $this->assertEquals($this->user->id(), $order->getCustomerId());
    $order->setCustomerId(0);
    $this->assertEquals(NULL, $order->getCustomer());
    $order->setCustomerId($this->user->id());
    $this->assertEquals($this->user, $order->getCustomer());
    $this->assertEquals($this->user->id(), $order->getCustomerId());

    $order->setEmail('commerce@example.com');
    $this->assertEquals('commerce@example.com', $order->getEmail());

    $order->setIpAddress('127.0.0.2');
    $this->assertEquals('127.0.0.2', $order->getIpAddress());

    $order->setBillingProfile($profile);
    $this->assertEquals($profile, $order->getBillingProfile());

    $order->setItems([$order_item, $another_order_item]);
    $this->assertEquals([$order_item, $another_order_item], $order->getItems());
    $this->assertTrue($order->hasItems());
    $order->removeItem($another_order_item);
    $this->assertEquals([$order_item], $order->getItems());
    $this->assertTrue($order->hasItem($order_item));
    $this->assertFalse($order->hasItem($another_order_item));
    $order->addItem($another_order_item);
    $this->assertEquals([$order_item, $another_order_item], $order->getItems());
    $this->assertTrue($order->hasItem($another_order_item));

    $this->assertEquals(new Price('8.00', 'USD'), $order->getTotalPrice());
    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
    ]);
    $order->addAdjustment($adjustments[0]);
    $order->addAdjustment($adjustments[1]);
    $adjustments = $order->getAdjustments();
    $this->assertEquals($adjustments, $order->getAdjustments());
    $order->removeAdjustment($adjustments[0]);
    $this->assertEquals(new Price('18.00', 'USD'), $order->getTotalPrice());
    $this->assertEquals([$adjustments[1]], $order->getAdjustments());
    $order->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $order->getAdjustments());
    $this->assertEquals(new Price('17.00', 'USD'), $order->getTotalPrice());
    // Add an adjustment to the second order item, confirm it's a part of the
    // order total, multiplied by quantity.
    $order->removeItem($another_order_item);
    $another_order_item->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Random fee',
      'amount' => new Price('5.00', 'USD'),
    ]));
    $order->addItem($another_order_item);
    $this->assertEquals(new Price('27.00', 'USD'), $order->getTotalPrice());

    $this->assertEquals('completed', $order->getState()->value);

    $order->setRefreshState(Order::REFRESH_ON_SAVE);
    $this->assertEquals(Order::REFRESH_ON_SAVE, $order->getRefreshState());

    $this->assertEquals('default', $order->getData('test', 'default'));
    $order->setData('test', 'value');
    $this->assertEquals('value', $order->getData('test', 'default'));

    $order->setCreatedTime(635879700);
    $this->assertEquals(635879700, $order->getCreatedTime());

    $order->setPlacedTime(635879800);
    $this->assertEquals(635879800, $order->getPlacedTime());

    $order->setCompletedTime(635879900);
    $this->assertEquals(635879900, $order->getCompletedTime());
  }

}
