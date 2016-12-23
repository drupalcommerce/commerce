<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests integration with order events.
 *
 * @group commerce
 */
class OrderIntegrationTest extends CommerceKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The log view builder.
   *
   * @var \Drupal\commerce_log\LogViewBuilder
   */
  protected $logViewBuilder;

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
    'commerce_log',
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
    $this->installEntitySchema('commerce_log');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->logStorage = $this->container->get('entity_type.manager')->getStorage('commerce_log');
    $this->logViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_log');

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
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
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
   * Tests that a log is generated when an order is placed.
   */
  public function testPlacedLog() {
    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $logs = $this->logStorage->loadByEntity($this->order);
    $this->assertEquals(1, count($logs));
    $log = reset($logs);
    $build = $this->logViewBuilder->view($log);
    $this->render($build);

    $this->assertText('The order was placed.');
  }

}
