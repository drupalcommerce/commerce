<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the order mailer.
 *
 * @group commerce
 */
class OrderMailerTest extends EntityKernelTestBase {
  use AssertMailTrait;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

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
    $this->installConfig(['system', 'commerce_product', 'commerce_order']);
    $this->container->get('commerce_price.currency_importer')->import('USD');
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
      'default_currency' => 'USD',
      'mail' => $this->randomString() . '@example.com',
    ]);
    $store->save();

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
      'store_id' => $store->id(),
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
   * Tests the order mailer.
   */
  public function testOrderMailer() {
    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $the_email = reset($mails);
    $this->assertEquals('text/html', $the_email['headers']['Content-Type']);
    $this->assertEquals($this->order->getStore()->getEmail(), $the_email['headers']['Bcc']);
  }

  /**
   * Tests the order mailer.
   */
  public function testOrderMailerDisabled() {
    $order_type = OrderType::load($this->order->bundle());
    $order_type->setSendReceipt(FALSE);
    $order_type->save();

    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $mails = $this->getMails();
    $this->assertEquals(0, count($mails));
  }

  /**
   * Tests disabling the bcc.
   */
  public function testOrderBccDisabled() {
    $order_type = OrderType::load($this->order->bundle());
    $order_type->setReceiptBcc(FALSE);
    $order_type->save();

    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $the_email = reset($mails);
    $this->assertFalse(isset($the_email['headers']['Bcc']));
  }

}
