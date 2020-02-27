<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\Core\Database\Database;

/**
 * Tests cart expiration.
 *
 * @group commerce
 */
class CartExpirationTest extends CartKernelTestBase {

  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
    $this->orderStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
  }

  /**
   * Tests expiration (cron and queue worker).
   */
  public function testExpiration() {
    $order_type = OrderType::load('default');
    $time = $this->container->get('datetime.time');
    $four_days_ago = $time->getRequestTime() - (86400 * 4);
    $two_days_ago = $time->getRequestTime() - (86400 * 2);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart1 */
    $cart1 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart1->save();
    $cart2 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart2->save();
    $cart3 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $two_days_ago,
      'changed' => $two_days_ago,
    ]);
    $cart3->save();
    $cart4 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
    ]);
    $cart4->save();
    // Setting the `changed` attribute doesn't work in save.
    $count = Database::getConnection()->update('commerce_order')
      ->fields(['changed' => $four_days_ago])
      ->condition('order_id', [$cart1->id(), $cart2->id()], 'IN')
      ->execute();
    $this->assertEquals(2, $count);

    // By default, cart expiration is disabled.
    // Confirm that no orders are queued.
    $this->container->get('commerce_cart.cron')->run();
    $this->assertEquals(0, \Drupal::queue('commerce_cart_expiration')->numberOfItems());

    // Set expiration to 3 days.
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', [
      'unit' => 'day',
      'number' => 3,
    ]);
    $order_type->save();

    // Confirm that cron has queued IDs.
    $this->container->get('commerce_cart.cron')->run();
    $this->assertEquals(1, \Drupal::queue('commerce_cart_expiration')->numberOfItems());

    // Disable cart expiration.
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', []);
    $order_type->save();

    // Confirm that $cart1 and $cart2 were not deleted.
    $this->container->get('cron')->run();
    $this->orderStorage->resetCache();
    $orders = $this->orderStorage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertEquals(4, count($orders));
    $this->assertNotNull($this->reloadEntity($cart1));
    $this->assertNotNull($this->reloadEntity($cart2));

    // Re-enable cart expiration.
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', [
      'unit' => 'day',
      'number' => 3,
    ]);
    $order_type->save();

    $this->container->get('commerce_cart.cron')->run();
    $this->assertEquals(1, \Drupal::queue('commerce_cart_expiration')->numberOfItems());

    // Confirm that $cart1 and $cart2 were deleted.
    $this->container->get('cron')->run();
    $this->orderStorage->resetCache();
    $orders = $this->orderStorage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertEquals(2, count($orders));
    $this->assertNull($this->reloadEntity($cart1));
    $this->assertNull($this->reloadEntity($cart2));
  }

  /**
   * Tests expiration when a queued order is no longer eligible for deletion.
   */
  public function testNonEligibleOrder() {
    $order_type = OrderType::load('default');
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', [
      'unit' => 'day',
      'number' => 3,
    ]);
    $order_type->save();
    $time = $this->container->get('datetime.time');
    $four_days_ago = $time->getRequestTime() - (86400 * 4);

    $cart1 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart1->save();
    $cart2 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart2->save();
    // Setting the `changed` attribute doesn't work in save.
    $count = Database::getConnection()->update('commerce_order')
      ->fields(['changed' => $four_days_ago])
      ->condition('order_id', [$cart1->id(), $cart2->id()], 'IN')
      ->execute();
    $this->assertEquals(2, $count);

    $this->container->get('commerce_cart.cron')->run();
    $this->assertEquals(1, \Drupal::queue('commerce_cart_expiration')->numberOfItems());

    $cart1->getState()->applyTransitionById('place');
    $cart1->save();
    // Confirm that $cart1 was skipped and $cart2 was deleted.
    $this->container->get('cron')->run();
    $this->orderStorage->resetCache();
    $orders = $this->orderStorage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertEquals(1, count($orders));
    $this->assertNotNull($this->reloadEntity($cart1));
    $this->assertNull($this->reloadEntity($cart2));
  }

  /**
   * Tests expiration when a queued order was already deleted.
   */
  public function testDeletedOrder() {
    $order_type = OrderType::load('default');
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', [
      'unit' => 'day',
      'number' => 3,
    ]);
    $order_type->save();
    $time = $this->container->get('datetime.time');
    $four_days_ago = $time->getRequestTime() - (86400 * 4);

    $cart1 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart1->save();
    $cart2 = Order::create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart2->save();
    // Setting the `changed` attribute doesn't work in save.
    $count = Database::getConnection()->update('commerce_order')
      ->fields(['changed' => $four_days_ago])
      ->condition('order_id', [$cart1->id(), $cart2->id()], 'IN')
      ->execute();
    $this->assertEquals(2, $count);

    $this->container->get('commerce_cart.cron')->run();
    $this->assertEquals(1, \Drupal::queue('commerce_cart_expiration')->numberOfItems());
    $cart1->delete();

    // Confirm that $cart2 was deleted.
    $this->container->get('cron')->run();
    $this->orderStorage->resetCache();
    $orders = $this->orderStorage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertEquals(0, count($orders));
    $this->assertNull($this->reloadEntity($cart2));
  }

}
