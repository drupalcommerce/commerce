<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Exception\CurrencyMismatchException;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the Order entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\Order
 *
 * @group commerce
 */
class OrderTest extends OrderKernelTestBase {

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
    'commerce_order_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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
   * @covers ::collectProfiles
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
   * @covers ::getTotalPaid
   * @covers ::setTotalPaid
   * @covers ::getBalance
   * @covers ::isPaid
   * @covers ::getState
   * @covers ::getRefreshState
   * @covers ::setRefreshState
   * @covers ::getData
   * @covers ::setData
   * @covers ::unsetData
   * @covers ::isLocked
   * @covers ::lock
   * @covers ::unlock
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getPlacedTime
   * @covers ::setPlacedTime
   * @covers ::getCompletedTime
   * @covers ::setCompletedTime
   * @covers ::getCalculationDate
   */
  public function testOrder() {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
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

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'store_id' => $this->store->id(),
    ]);
    $order->save();

    $order->setOrderNumber(7);
    $this->assertEquals(7, $order->getOrderNumber());
    $this->assertFalse($order->isPaid());

    $order->setStore($this->store);
    $this->assertEquals($this->store, $order->getStore());
    $this->assertEquals($this->store->id(), $order->getStoreId());
    $order->setStoreId(0);
    $this->assertEquals(NULL, $order->getStore());
    $order->setStoreId($this->store->id());
    $this->assertEquals($this->store, $order->getStore());
    $this->assertEquals($this->store->id(), $order->getStoreId());

    $this->assertInstanceOf(UserInterface::class, $order->getCustomer());
    $this->assertTrue($order->getCustomer()->isAnonymous());
    $this->assertEquals(0, $order->getCustomerId());
    $order->setCustomer($this->user);
    $this->assertEquals($this->user, $order->getCustomer());
    $this->assertEquals($this->user->id(), $order->getCustomerId());
    $this->assertTrue($order->getCustomer()->isAuthenticated());
    // Non-existent/deleted user ID.
    $order->setCustomerId(888);
    $this->assertInstanceOf(UserInterface::class, $order->getCustomer());
    $this->assertTrue($order->getCustomer()->isAnonymous());
    $this->assertEquals(888, $order->getCustomerId());
    $order->setCustomerId($this->user->id());
    $this->assertEquals($this->user, $order->getCustomer());
    $this->assertEquals($this->user->id(), $order->getCustomerId());

    $order->setEmail('commerce@example.com');
    $this->assertEquals('commerce@example.com', $order->getEmail());

    $order->setIpAddress('127.0.0.2');
    $this->assertEquals('127.0.0.2', $order->getIpAddress());

    $order->setBillingProfile($profile);
    $this->assertEquals($profile, $order->getBillingProfile());

    $profiles = $order->collectProfiles();
    $this->assertCount(1, $profiles);
    $this->assertArrayHasKey('billing', $profiles);
    $this->assertEquals($profile, $profiles['billing']);

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
    $order->addAdjustment($adjustments[0]);
    $order->addAdjustment($adjustments[1]);
    $this->assertEquals($adjustments, $order->getAdjustments());
    $this->assertEquals($adjustments, $order->getAdjustments(['custom', 'fee']));
    $this->assertEquals([$adjustments[0]], $order->getAdjustments(['custom']));
    $this->assertEquals([$adjustments[1]], $order->getAdjustments(['fee']));
    $order->removeAdjustment($adjustments[0]);
    $this->assertEquals(new Price('8.00', 'USD'), $order->getSubtotalPrice());
    $this->assertEquals(new Price('18.00', 'USD'), $order->getTotalPrice());
    $this->assertEquals([$adjustments[1]], $order->getAdjustments());
    $order->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $order->getAdjustments());
    $this->assertEquals(new Price('17.00', 'USD'), $order->getTotalPrice());
    // Confirm that locked adjustments persist after clear.
    // Custom adjustments are locked by default.
    $order->addAdjustment(new Adjustment([
      'type' => 'fee',
      'label' => 'Random fee',
      'amount' => new Price('10.00', 'USD'),
    ]));
    $order->clearAdjustments();
    $this->assertEquals($adjustments, $order->getAdjustments());

    $this->assertEquals($adjustments, $order->collectAdjustments());
    $this->assertEquals($adjustments, $order->collectAdjustments(['custom', 'fee']));
    $this->assertEquals([$adjustments[0]], $order->collectAdjustments(['custom']));
    $this->assertEquals([$adjustments[1]], $order->collectAdjustments(['fee']));

    $this->assertEquals(new Price('0', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('17.00', 'USD'), $order->getBalance());
    $this->assertFalse($order->isPaid());

    $order->setTotalPaid(new Price('7.00', 'USD'));
    $this->assertEquals(new Price('7.00', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('10.00', 'USD'), $order->getBalance());
    $this->assertFalse($order->isPaid());

    $order->setTotalPaid(new Price('17.00', 'USD'));
    $this->assertEquals(new Price('17.00', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('0', 'USD'), $order->getBalance());
    $this->assertTrue($order->isPaid());

    $order->setTotalPaid(new Price('27.00', 'USD'));
    $this->assertEquals(new Price('27.00', 'USD'), $order->getTotalPaid());
    $this->assertEquals(new Price('-10.00', 'USD'), $order->getBalance());
    $this->assertTrue($order->isPaid());

    $this->assertEquals('completed', $order->getState()->getId());

    // Confirm that free orders are considered paid after placement.
    $order->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => '100% off',
      'amount' => new Price('-17.00', 'USD'),
    ]));
    $order->setTotalPaid(new Price('0', 'USD'));
    $this->assertTrue($order->getTotalPrice()->isZero());
    $this->assertTrue($order->isPaid());
    $order->set('state', 'draft');
    $this->assertFalse($order->isPaid());

    $order->setRefreshState(Order::REFRESH_ON_SAVE);
    $this->assertEquals(Order::REFRESH_ON_SAVE, $order->getRefreshState());

    $this->assertEquals('default', $order->getData('test', 'default'));
    $order->setData('test', 'value');
    $this->assertEquals('value', $order->getData('test', 'default'));
    $order->unsetData('test');
    $this->assertNull($order->getData('test'));
    $this->assertEquals('default', $order->getData('test', 'default'));

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

    $date = $order->getCalculationDate();
    $this->assertEquals($order->getPlacedTime(), $date->format('U'));
    $order->set('placed', NULL);
    $date = $order->getCalculationDate();
    $this->assertEquals(\Drupal::time()->getRequestTime(), $date->format('U'));
  }

  /**
   * @covers ::preSave
   */
  public function testPreSave() {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'uid' => '888',
      'billing_profile' => $profile,
      'state' => 'completed',
    ]);
    $order->save();

    // Confirm that saving the order clears an invalid customer ID.
    $this->assertEquals(0, $order->getCustomerId());

    // Confirm that saving the order reassigns the billing profile.
    $order->save();
    $this->assertEquals(0, $order->getBillingProfile()->getOwnerId());
    $this->assertEquals($profile->id(), $order->getBillingProfile()->id());

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
    $order->setItems([$order_item, $another_order_item]);
    $this->assertCount(2, $order->get('order_items'));
    $another_order_item->delete();
    // Assert that saving the order fixes the reference to a deleted order item.
    $order->save();
    $this->reloadEntity($order);
    $this->assertCount(1, $order->get('order_items'));
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

  /**
   * Tests the handling of legacy order item adjustments on adjustment clear.
   *
   * @covers ::clearAdjustments
   * @covers ::collectAdjustments
   */
  public function testHandlingLegacyOrderItemAdjustments() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('10.00', 'USD'),
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => '10% off',
          'amount' => new Price('-1.00', 'USD'),
          'percentage' => '0.1',
        ]),
        new Adjustment([
          'type' => 'fee',
          'label' => 'Random fee',
          'amount' => new Price('2.00', 'USD'),
        ]),
      ],
      'uses_legacy_adjustments' => TRUE,
    ]);
    $order_item->save();

    $order = Order::create([
      'type' => 'default',
      'order_items' => [$order_item],
      'state' => 'draft',
    ]);

    // Confirm that legacy adjustments are multiplied by quantity.
    $adjustments = $order->collectAdjustments();
    $this->assertCount(2, $adjustments);
    $this->assertEquals('-2.00', $adjustments[0]->getAmount()->getNumber());
    $this->assertEquals('4.00', $adjustments[1]->getAmount()->getNumber());

    // Confirm that the legacy order item adjustments are converted on clear.
    $order->clearAdjustments();
    $order_items = $order->getItems();
    $order_item = reset($order_items);
    $adjustments = $order_item->getAdjustments();

    $this->assertFalse($order_item->usesLegacyAdjustments());
    $this->assertCount(1, $adjustments);
    $this->assertEquals('-2.00', $adjustments[0]->getAmount()->getNumber());

    // The order item adjustments are no longer multiplied by quantity.
    $this->assertEquals($adjustments, $order->collectAdjustments());
  }

  /**
   * Tests the order total recalculation logic.
   *
   * @covers ::recalculateTotalPrice
   */
  public function testTotalCalculation() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->save();

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $another_order_item */
    $another_order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('3.00', 'USD'),
    ]);
    $another_order_item->save();
    $another_order_item = $this->reloadEntity($another_order_item);

    $adjustments = [];
    $adjustments[0] = new Adjustment([
      'type' => 'tax',
      'label' => 'Tax',
      'amount' => new Price('100.00', 'USD'),
      'included' => TRUE,
    ]);
    $adjustments[1] = new Adjustment([
      'type' => 'tax',
      'label' => 'Tax',
      'amount' => new Price('2.121', 'USD'),
      'source_id' => 'us_sales_tax',
    ]);
    $adjustments[2] = new Adjustment([
      'type' => 'tax',
      'label' => 'Tax',
      'amount' => new Price('5.344', 'USD'),
      'source_id' => 'us_sales_tax',
    ]);

    // Included adjustments do not affect the order total.
    $order->addAdjustment($adjustments[0]);
    $order_item->addAdjustment($adjustments[1]);
    $another_order_item->addAdjustment($adjustments[2]);
    $order->setItems([$order_item, $another_order_item]);
    $order->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->reloadEntity($order);

    $collected_adjustments = $order->collectAdjustments();
    $this->assertCount(3, $collected_adjustments);
    $this->assertEquals($adjustments[1], $collected_adjustments[0]);
    $this->assertEquals($adjustments[2], $collected_adjustments[1]);
    $this->assertEquals($adjustments[0], $collected_adjustments[2]);
    // The total will be correct only if the adjustments were correctly
    // combined, and rounded.
    $this->assertEquals(new Price('14.47', 'USD'), $order->getTotalPrice());

    // Test handling deleted order items + non-inclusive adjustments.
    $order->addAdjustment($adjustments[1]);
    $order_item->delete();
    $another_order_item->delete();
    $order->recalculateTotalPrice();
    $this->assertNull($order->getTotalPrice());
  }

  /**
   * Tests the generation of the 'placed' and 'completed' timestamps.
   */
  public function testTimestamps() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'state' => 'draft',
    ]);
    $order->save();
    $order = $this->reloadEntity($order);

    $this->assertNull($order->getPlacedTime());
    $this->assertNull($order->getCompletedTime());
    // Transitioning the order out of the draft state should set the timestamps.
    $order->getState()->applyTransitionById('place');
    $order->save();
    $this->assertEquals($order->getPlacedTime(), \Drupal::time()->getRequestTime());
    $this->assertEquals($order->getCompletedTime(), \Drupal::time()->getRequestTime());
  }

  /**
   * Tests the order with order items using different currencies.
   *
   * @covers ::getSubtotalPrice
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   */
  public function testMultipleCurrencies() {
    $currency_importer = $this->container->get('commerce_price.currency_importer');
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
   * Tests that the paid event is dispatched when the balance reaches zero.
   */
  public function testPaidEvent() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('10.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'state' => 'draft',
    ]);
    $order->save();
    $this->assertNull($order->getData('order_test_called'));

    $order->setTotalPaid(new Price('20.00', 'USD'));
    $order->save();
    $this->assertEquals(1, $order->getData('order_test_called'));

    // Confirm that the event is not dispatched the second time the balance
    // reaches zero.
    $order->setTotalPaid(new Price('10.00', 'USD'));
    $order->save();
    $order->setTotalPaid(new Price('20.00', 'USD'));
    $order->save();
    $this->assertEquals(1, $order->getData('order_test_called'));

    // Confirm that the event is dispatched for orders created as paid.
    $another_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'total_paid' => new Price('20.00', 'USD'),
      'state' => 'draft',
    ]);
    $another_order->save();
    $this->assertEquals(1, $another_order->getData('order_test_called'));
  }

}
