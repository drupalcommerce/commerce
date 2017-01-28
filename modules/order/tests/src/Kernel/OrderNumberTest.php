<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the setting of the order number during order placement.
 *
 * @group commerce
 */
class OrderNumberTest extends CommerceKernelTestBase {

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
  }

  /**
   * Tests setting the order number.
   */
  public function testSetOrderNumber() {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

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
    $this->assertEquals($order1->id(), $order1->getOrderNumber(), 'During placement transition, the order number is set to the order ID.');

    /** @var \Drupal\commerce_order\Entity\Order $order2 */
    $order2 = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '9999',
      'store_id' => $this->store->id(),
    ]);
    $order2->save();

    $transition = $order2->getState()->getTransitions();
    $order2->getState()->applyTransition($transition['place']);
    $order2->save();
    $this->assertEquals('9999', $order2->getOrderNumber(), 'Explicitly set order number should not get overridden.');
  }

}
