<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the assignment and reassignment of an order.
 *
 * @group commerce
 */
class OrderAssignmentTest extends CommerceKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The default order user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

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
    $this->user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

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
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();
    $product->addVariation($variation1)->save();

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

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    // Add order item.
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();
    $order->addItem($order_item1);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests if a single order cannot be reassigned if it already is.
   */
  public function testSingleOrderReassignmentImpossible() {
    // The new user we will assign the order to.
    $new_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    /** @var \Drupal\commerce_order\OrderAssignment $assignment_service */
    $assignment_service = $this->container->get('commerce_order.order_assignment');
    $assignment_service->assign($this->order, $new_user);

    // Check that the reassignment worked.
    $this->assertEquals($this->user->id(), $this->order->getCustomerId());
  }

  /**
   * Tests if a single order can be reassigned even when it already is.
   */
  public function testSingleOrderReassignment() {
    // The new user we will assign the order to.
    $new_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    /** @var \Drupal\commerce_order\OrderAssignment $assignment_service */
    $assignment_service = $this->container->get('commerce_order.order_assignment');
    $assignment_service->assign($this->order, $new_user, TRUE);

    // Check that the reassignment worked.
    $this->assertEquals($new_user->id(), $this->order->getCustomerId());
  }

  /**
   * Tests that multiple orders cannot be reassigned if it already is.
   */
  public function testMultipleOrdersReassignmentImpossible() {
    $order2 = $this->order->createDuplicate();
    $order2->save();

    // The new user we will assign the order to.
    $new_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    // Create our array.
    $orders = [$this->order, $order2];

    /** @var \Drupal\commerce_order\OrderAssignment $assignment_service */
    $assignment_service = $this->container->get('commerce_order.order_assignment');
    $assignment_service->assignMultiple($orders, $new_user);

    $this->assertEquals($this->user->id(), $this->order->getCustomerId());
    $this->assertEquals($this->user->id(), $order2->getCustomerId());
  }

  /**
   * Tests that multiple orders can be reassigned even when they already are.
   */
  public function testMultipleOrdersReassignment() {
    $order2 = $this->order->createDuplicate();
    $order2->save();

    // The new user we will assign the order to.
    $new_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    // Create our array.
    $orders = [$this->order, $order2];

    /** @var \Drupal\commerce_order\OrderAssignment $assignment_service */
    $assignment_service = $this->container->get('commerce_order.order_assignment');
    $assignment_service->assignMultiple($orders, $new_user, TRUE);

    $this->assertEquals($new_user->id(), $this->order->getCustomerId());
    $this->assertEquals($new_user->id(), $order2->getCustomerId());
  }

}
