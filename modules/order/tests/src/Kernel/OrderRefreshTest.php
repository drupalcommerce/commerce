<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the order refresh process.
 *
 * @group commerce
 */
class OrderRefreshTest extends EntityKernelTestBase {

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

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
    'system', 'field', 'options', 'user', 'entity',
    'entity_reference_revisions', 'path',
    'views', 'address', 'profile', 'state_machine',
    'inline_entity_form', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product',
    'commerce_order', 'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    $this->orderRefresh = $this->container->get('commerce_order.order_refresh');

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->orderItemStorage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_order_item');

    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
      'default_currency' => 'USD',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);

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
    // Save variation again to generate its title.
    $variation1->save();

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
    // Save variation again to generate its title.
    $variation2->save();

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
      'uid' => 0,
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the "needsRefresh" method and refresh settings.
   *
   * @group failing
   */
  public function testOrderCanRefresh() {
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2, [
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);
    $this->order->addItem($order_item);
    $this->order->save();

    $this->assertFalse($this->orderRefresh->needsRefresh($this->order));

    $order_type = OrderType::load($this->order->bundle());
    $order_type->setRefreshFrequency(1)->save();

    sleep(1);
    $this->assertTrue($this->orderRefresh->needsRefresh($this->order));

    $order_type->setRefreshMode(OrderType::REFRESH_OWNER)->save();
    $this->container->get('current_user')->setAccount($this->user);

    sleep(1);
    $this->assertFalse($this->orderRefresh->needsRefresh($this->order));
    $this->order->setOwner($this->user);
    $this->assertTrue($this->orderRefresh->needsRefresh($this->order));

    sleep(1);
    $workflow = $this->order->getState()->getWorkflow();
    $this->order->getState()->applyTransition($workflow->getTransition('place'));
    $this->order->save();
    $this->assertFalse($this->orderRefresh->needsRefresh($this->order));
  }

  /**
   * Tests that an order item's title is updated based on product changes.
   */
  public function testOrderItemTitleUpdate() {
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2, [
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($order_item->label(), 'Default testing product');
    $this->order->addItem($order_item);
    $this->order->save();

    $this->variation2->getProduct()->setTitle('Changed title')->save();
    $this->variation2->save();
    $this->orderRefresh->refresh($this->order);

    $this->variation2 = $this->reloadEntity($this->variation2);
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($order_item->label(), 'Changed title');
  }

  /**
   * Tests that an order item's unit price is set and updated on refresh.
   */
  public function testUnitPriceRefresh() {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);

    $this->order->addItem($order_item);
    $this->order->save();
    $this->orderRefresh->refresh($this->order);
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($this->variation2->getPrice(), $order_item->getUnitPrice());

    $this->variation2->setPrice(new Price('12.00', 'USD'))->save();

    $this->orderRefresh->refresh($this->order);
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($this->variation2->getPrice(), $order_item->getUnitPrice());
  }

  /**
   * Tests the order refresh, with the availability processor.
   */
  public function testAvailabilityOrderRefr() {
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation1, [
      'unit_price' => new Price('2.00', 'USD'),
    ]);
    $order_item->save();
    $order_item = $this->reloadEntity($order_item);

    $another_order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation2, [
      'unit_price' => new Price('3.00', 'USD'),
      'quantity' => '2',

    ]);
    $another_order_item->save();
    $another_order_item = $this->reloadEntity($another_order_item);

    $this->order->setItems([$order_item, $another_order_item])->save();
    $this->assertEquals(2, count($this->order->getItems()));

    $this->orderRefresh->refresh($this->order);
    $this->assertEquals(1, count($this->order->getItems()));
  }

}
