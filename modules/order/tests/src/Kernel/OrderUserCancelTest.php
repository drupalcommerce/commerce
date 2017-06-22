<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests for user deletion.
 *
 * @group commerce
 */
class OrderUserCancelTest extends CommerceKernelTestBase {

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

    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installConfig(['user']);

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

    // Create an anonymous user.
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    // Insert a row for the anonymous user.
    $storage
      ->create([
        'uid' => 0,
        'name' => '',
        'status' => 0,
      ])
      ->save();
  }

  /**
   * Delete account and remove all draft orders, anonymize remaining orders.
   */
  public function testUserCancelDelete() {
    $user = $this->createUser();

    /** @var \Drupal\commerce_order\Entity\Order $order1 */
    $order1 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'store_id' => $this->store->id(),
    ]);
    $order1->save();

    $transition = $order1->getState()->getTransitions();
    $order1->getState()->applyTransition($transition['place']);
    $order1->save();

    /** @var \Drupal\commerce_order\Entity\Order $order1 */
    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'store_id' => $this->store->id(),
    ]);
    $order2->save();

    user_cancel([], $user->id(), 'user_cancel_delete');
    $batch = &batch_get();
    $batch['progressive'] = FALSE;
    batch_process();

    $order1 = $this->reloadEntity($order1);

    $this->assertTrue(($order1->getCustomerId() == 0), 'Order of the user has been attributed to anonymous user.');
    $this->assertFalse(Order::load($order2->id()), 'Draft order of the user has been deleted.');
  }

  /**
   * Delete account and anonymize all orders.
   */
  public function testUserCancelReassign() {
    $user = $this->createUser();

    /** @var \Drupal\commerce_order\Entity\Order $order1 */
    $order1 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'store_id' => $this->store->id(),
    ]);
    $order1->save();

    $transition = $order1->getState()->getTransitions();
    $order1->getState()->applyTransition($transition['place']);
    $order1->save();

    /** @var \Drupal\commerce_order\Entity\Order $order1 */
    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'store_id' => $this->store->id(),
    ]);
    $order2->save();

    // Run batch for user cancellation.
    user_cancel([], $user->id(), 'user_cancel_reassign');
    $batch = &batch_get();
    $batch['progressive'] = FALSE;
    batch_process();

    $order1 = $this->reloadEntity($order1);
    $order2 = $this->reloadEntity($order2);

    $this->assertTrue(($order1->getCustomerId() == 0), 'Order of the user has been attributed to anonymous user.');
    $this->assertTrue(($order2->getCustomerId() == 0), 'Draft order of the user has been attributed to anonymous user.');
  }

}
