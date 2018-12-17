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
 * Tests the order assignment service.
 *
 * @coversDefaultClass \Drupal\commerce_order\OrderAssignment
 * @group commerce
 */
class OrderAssignmentTest extends CommerceKernelTestBase {

  /**
   * The order assignment service.
   *
   * @var \Drupal\commerce_order\OrderAssignmentInterface
   */
  protected $orderAssignment;

  /**
   * A test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A test user.
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

    $variation = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('12.00', 'USD'),
      'status' => TRUE,
    ]);
    $variation->save();

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

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'order_number' => '6',
      'store_id' => $this->store->id(),
      'uid' => $this->user->id(),
      'mail' => $this->user->getEmail(),
      'ip_address' => '127.0.0.1',
      'billing_profile' => $profile,
      'state' => 'draft',
    ]);
    $order->save();

    $order_item = $order_item_storage->createFromPurchasableEntity($variation, [
      'order_id' => $order->id(),
    ]);
    $order_item->save();
    $this->order = $this->reloadEntity($order);

    $this->orderAssignment = $this->container->get('commerce_order.order_assignment');
  }

  /**
   * Tests assigning an order to a new customer.
   *
   * @covers ::assignMultiple
   * @covers ::assign
   */
  public function testAssign() {
    $another_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->orderAssignment->assignMultiple([$this->order], $another_user);

    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals($another_user->id(), $this->order->getCustomerId());
    $this->assertEquals($another_user->getEmail(), $this->order->getEmail());
    $this->assertEquals($another_user->id(), $this->order->getBillingProfile()->getOwnerId());
  }

}
