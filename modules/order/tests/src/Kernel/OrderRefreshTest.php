<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_order\OrderRefresh;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the order refresh process.
 *
 * @group commerce
 */
class OrderRefreshTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A sample variation.
   *
   * Has a SKU which will flag availability service removal.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation1;

  /**
   * A sample variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation2;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->orderItemStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    // Turn off title generation to allow explicit values to be used.
    $variation_type = ProductVariationType::load('default');
    $variation_type->setGenerateTitle(FALSE);
    $variation_type->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
      'price' => new Price('2.00', 'USD'),
    ]);
    $variation1->save();
    $product->addVariation($variation1)->save();
    $this->variation1 = $this->reloadEntity($variation1);

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('3.00', 'USD'),
    ]);
    $variation2->save();
    $product->addVariation($variation2)->save();
    $this->variation2 = $this->reloadEntity($variation2);

    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the shouldRefresh() logic.
   */
  public function testShouldRefresh() {
    $order_refresh = $this->createOrderRefresh(time() + 3600);

    $order_type = OrderType::load($this->order->bundle());
    $order_type->setRefreshMode(OrderType::REFRESH_CUSTOMER)->save();
    // Order does not belong to the current user.
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    $this->assertEmpty($order_refresh->shouldRefresh($this->order));
    // Order belongs to the current user.
    $this->container->get('current_user')->setAccount($this->user);
    $this->assertNotEmpty($order_refresh->shouldRefresh($this->order));

    // Order should be refreshed for any user.
    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    $order_type = OrderType::load($this->order->bundle());
    $order_type->setRefreshMode(OrderType::REFRESH_ALWAYS)->save();
    $this->assertNotEmpty($order_refresh->shouldRefresh($this->order));
  }

  /**
   * Tests the needsRefresh() logic.
   */
  public function testNeedsRefresh() {
    $order_refresh = $this->createOrderRefresh();
    // Non-draft order.
    $this->order->state = 'completed';
    $this->assertEmpty($order_refresh->needsRefresh($this->order));
    $this->order->state = 'draft';

    // Day-change, under refresh frequency.
    $order_refresh = $this->createOrderRefresh(mktime(0, 1, 0, 2, 24, 2016));
    $this->order->setChangedTime(mktime(23, 59, 59, 2, 23, 2016));
    $this->assertNotEmpty($order_refresh->needsRefresh($this->order));

    // Under refresh frequency.
    $order_refresh = $this->createOrderRefresh(mktime(23, 12, 0, 2, 24, 2016));
    $this->order->setChangedTime(mktime(23, 11, 0, 2, 24, 2016));
    $this->assertEmpty($order_refresh->needsRefresh($this->order));

    // Over refresh frequency.
    $order_refresh = $this->createOrderRefresh(mktime(23, 10, 0, 2, 24, 2016));
    $this->order->setChangedTime(mktime(23, 0, 0, 2, 24, 2016));
    $this->assertNotEmpty($order_refresh->needsRefresh($this->order));
  }

  /**
   * Tests that the order item title and unit price are kept up to date.
   */
  public function testOrderItemRefresh() {
    $order_refresh = $this->createOrderRefresh();
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->setRefreshState(Order::REFRESH_SKIP);
    $this->order->save();

    $this->assertEquals($order_item->label(), $this->variation2->getTitle());
    $this->assertEquals($order_item->getUnitPrice(), $this->variation2->getPrice());

    $this->variation2->setTitle('Changed title');
    $this->variation2->setPrice(new Price('12.00', 'USD'));
    $this->variation2->save();
    $order_refresh->refresh($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($order_item->label(), $this->variation2->getTitle());
    $this->assertEquals($order_item->getUnitPrice(), $this->variation2->getPrice());

    // Confirm that overridden unit prices stay untouched.
    $unit_price = new Price('15.00', 'USD');
    $order_item->setUnitPrice($unit_price, TRUE);
    $this->variation2->setTitle('Changed title2');
    $this->variation2->setPrice(new Price('16.00', 'USD'));
    $this->variation2->save();
    $order_refresh->refresh($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($this->variation2->getTitle(), $order_item->label());
    $this->assertEquals($unit_price, $order_item->getUnitPrice());
  }

  /**
   * Tests the order refresh, with the availability processor.
   */
  public function testAvailabilityOrderRefresh() {
    $order_refresh = $this->createOrderRefresh();
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation1);
    $order_item->save();
    $another_order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2);
    $another_order_item->save();

    $this->order->setItems([$order_item, $another_order_item]);
    $this->order->setRefreshState(Order::REFRESH_SKIP);
    $this->order->save();
    $this->assertEquals(2, count($this->order->getItems()));

    $order_refresh->refresh($this->order);
    $this->assertEquals(1, count($this->order->getItems()));
  }

  /**
   * Tests the order refresh invoking by the order storage.
   */
  public function testStorage() {
    // Confirm that REFRESH_ON_SAVE happens by default.
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation1);
    $order_item->save();
    $another_order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2);
    $another_order_item->save();
    $this->order->setItems([$order_item, $another_order_item]);
    $this->order->save();
    $this->assertEquals(1, count($this->order->getItems()));
    $this->assertNull($this->order->getRefreshState());

    // Test REFRESH_ON_LOAD.
    $old_title = $this->variation2->getTitle();
    $this->variation2->setTitle('Changed title');
    $this->variation2->save();
    $this->order->setRefreshState(Order::REFRESH_ON_LOAD);
    $this->order->save();
    $another_order_item = $this->reloadEntity($another_order_item);
    $this->assertEquals(Order::REFRESH_ON_LOAD, $this->order->getRefreshState());
    $this->assertEquals($old_title, $another_order_item->getTitle());

    sleep(1);
    $old_changed_time = $this->order->getChangedTime();
    $this->order = $this->reloadEntity($this->order);
    $another_order_item = $this->reloadEntity($another_order_item);
    $this->assertNotEquals($old_changed_time, $this->order->getChangedTime());
    $this->assertEquals('Changed title', $another_order_item->getTitle());
    $this->assertNull($this->order->getRefreshState());
  }

  /**
   * Creates an OrderRefresh instance with the given current time.
   *
   * @param int $current_time
   *   The current time as a UNIX timestamp. Defaults to time().
   *
   * @return \Drupal\commerce_order\OrderRefreshInterface
   *   The order refresh.
   */
  protected function createOrderRefresh($current_time = NULL) {
    $current_time = $current_time ?: time();
    $entity_type_manager = $this->container->get('entity_type.manager');
    $chain_price_resolver = $this->container->get('commerce_price.chain_price_resolver');
    $user = $this->container->get('current_user');
    $time = $this->prophesize(TimeInterface::class);
    $time->getCurrentTime()->willReturn($current_time);
    $time = $time->reveal();
    $order_refresh = new OrderRefresh($entity_type_manager, $chain_price_resolver, $user, $time);
    $order_refresh->addProcessor($this->container->get('commerce_order.availability_order_processor'));

    return $order_refresh;
  }

}
