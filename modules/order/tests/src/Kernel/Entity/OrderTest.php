<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Exception\CurrencyMismatchException;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Order entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\Order
 *
 * @group commerce
 */
class OrderTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
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
   * @covers ::removeAdjustment
   * @covers ::clearAdjustments
   * @covers ::collectAdjustments
   * @covers ::getSubtotalPrice
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   * @covers ::getState
   * @covers ::getRefreshState
   * @covers ::setRefreshState
   * @covers ::getData
   * @covers ::setData
   * @covers ::isLocked
   * @covers ::lock
   * @covers ::unlock
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
      'quantity' => '1',
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
    $this->assertNotEmpty($order->hasItems());
    $order->removeItem($another_order_item);
    $this->assertEquals([$order_item], $order->getItems());
    $this->assertNotEmpty($order->hasItem($order_item));
    $this->assertEmpty($order->hasItem($another_order_item));
    $order->addItem($another_order_item);
    $this->assertEquals([$order_item, $another_order_item], $order->getItems());
    $this->assertNotEmpty($order->hasItem($another_order_item));

    $this->assertEquals(new Price('8.00', 'USD'), $order->getTotalPrice());
    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
      'locked' => TRUE,
    ]);
    // Included adjustments do not affect the order total.
    $adjustments[] = new Adjustment([
      'type' => 'tax',
      'label' => 'Tax',
      'amount' => new Price('12.00', 'USD'),
      'included' => TRUE,
    ]);
    $order->addAdjustment($adjustments[0]);
    $order->addAdjustment($adjustments[1]);
    $order->addAdjustment($adjustments[2]);
    $this->assertEquals($adjustments, $order->getAdjustments());
    $collected_adjustments = $order->collectAdjustments();
    $this->assertEquals($adjustments[0]->getAmount(), $collected_adjustments[0]->getAmount());
    $this->assertEquals($adjustments[1]->getAmount(), $collected_adjustments[1]->getAmount());
    $this->assertEquals($adjustments[2]->getAmount(), $collected_adjustments[2]->getAmount());
    $order->removeAdjustment($adjustments[0]);
    $this->assertEquals(new Price('8.00', 'USD'), $order->getSubtotalPrice());
    $this->assertEquals(new Price('18.00', 'USD'), $order->getTotalPrice());
    $this->assertEquals([$adjustments[1], $adjustments[2]], $order->getAdjustments());
    $order->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $order->getAdjustments());
    $this->assertEquals(new Price('17.00', 'USD'), $order->getTotalPrice());
    // Add an adjustment to the second order item, confirm it's a part of the
    // order total, multiplied by quantity.
    $order->removeItem($another_order_item);
    $order_item_adjustments = [];
    $order_item_adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Random fee',
      'amount' => new Price('5.00', 'USD'),
    ]);
    $order_item_adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Non-random fee',
      'amount' => new Price('7.00', 'USD'),
      'locked' => TRUE,
    ]);
    $multiplied_order_item_adjustments = [];
    $multiplied_order_item_adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Random fee',
      'amount' => new Price('10.00', 'USD'),
    ]);
    $multiplied_order_item_adjustments[] = new Adjustment([
      'type' => 'fee',
      'label' => 'Non-random fee',
      'amount' => new Price('14.00', 'USD'),
      'locked' => TRUE,
    ]);
    $another_order_item->setAdjustments($order_item_adjustments);
    $order->addItem($another_order_item);
    $this->assertEquals(new Price('41.00', 'USD'), $order->getTotalPrice());
    $collected_adjustments = $order->collectAdjustments();
    $this->assertEquals($multiplied_order_item_adjustments[0], $collected_adjustments[0]);
    $this->assertEquals($multiplied_order_item_adjustments[1], $collected_adjustments[1]);
    // Confirm that locked adjustments persist after clear.
    // Custom adjustments are locked by default.
    $order->setAdjustments($adjustments);
    $order->clearAdjustments();
    unset($adjustments[2]);
    unset($multiplied_order_item_adjustments[0]);
    $this->assertEquals(array_merge($multiplied_order_item_adjustments, $adjustments), $order->collectAdjustments());

    $this->assertEquals('completed', $order->getState()->value);

    $order->setRefreshState(Order::REFRESH_ON_SAVE);
    $this->assertEquals(Order::REFRESH_ON_SAVE, $order->getRefreshState());

    $this->assertEquals('default', $order->getData('test', 'default'));
    $order->setData('test', 'value');
    $this->assertEquals('value', $order->getData('test', 'default'));

    $this->assertFalse($order->isLocked());
    $order->lock();
    $this->assertTrue($order->isLocked());
    $order->unlock();
    $this->assertFalse($order->isLocked());

    $order->setCreatedTime(635879700);
    $this->assertEquals(635879700, $order->getCreatedTime());

    $order->setPlacedTime(635879800);
    $this->assertEquals(635879800, $order->getPlacedTime());

    $order->setCompletedTime(635879900);
    $this->assertEquals(635879900, $order->getCompletedTime());
  }

  /**
   * Tests the order with order items using different currencies.
   *
   * @covers ::getSubtotalPrice
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   */
  public function testMultipleCurrencies() {
    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('EUR');

    $usd_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $usd_order_item->save();
    $eur_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('3.00', 'EUR'),
    ]);
    $eur_order_item->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->save();

    // The order currency should match the currency of the first order item.
    $this->assertNull($order->getTotalPrice());
    $order->addItem($usd_order_item);
    $this->assertEquals($usd_order_item->getTotalPrice(), $order->getTotalPrice());

    // Replacing the order item should replace the order total and its currency.
    $order->removeItem($usd_order_item);
    $order->addItem($eur_order_item);
    $this->assertEquals($eur_order_item->getTotalPrice(), $order->getTotalPrice());

    // Adding a second order item with a different currency should fail.
    $currency_mismatch = FALSE;
    try {
      $order->addItem($usd_order_item);
    }
    catch (CurrencyMismatchException $e) {
      $currency_mismatch = TRUE;
    }
    $this->assertTrue($currency_mismatch);
  }

  /**
   * Tests that an order's email updates with the customer.
   */
  public function testOrderEmail() {
    $customer = $this->createUser(['mail' => 'test@example.com']);
    $order_with_customer = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'uid' => $customer,
    ]);
    $order_with_customer->save();
    $this->assertEquals($customer->getEmail(), $order_with_customer->getEmail());

    $order_without_customer = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order_without_customer->save();
    $this->assertEquals('', $order_without_customer->getEmail());
    $order_without_customer->setCustomer($customer);
    $order_without_customer->save();
    $this->assertEquals($customer->getEmail(), $order_without_customer->getEmail());
  }

}
