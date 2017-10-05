<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the sending of order receipt emails.
 *
 * @group commerce
 */
class OrderReceiptTest extends CommerceKernelTestBase {

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
    'entity_reference_revisions',
    'path',
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

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

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

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [$order_item1],
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the order receipt.
   */
  public function testOrderReceipt() {
    $transition = $this->order->getState()->getTransitions();
    $this->order->setOrderNumber('2017/01');
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $the_email = reset($mails);
    $this->assertEquals('text/html; charset=UTF-8;', $the_email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $the_email['headers']['Content-Transfer-Encoding']);
    $this->assertEquals('Order #2017/01 confirmed', $the_email['subject']);
    $this->assertEmpty(isset($the_email['headers']['Bcc']));
  }

  /**
   * Tests disabling the order receipt.
   */
  public function testOrderReceiptDisabled() {
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
   * Tests the BCC functionality.
   */
  public function testOrderReceiptBcc() {
    $order_type = OrderType::load('default');
    $order_type->setReceiptBcc('bcc@example.com');
    $order_type->save();

    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['place']);
    $this->order->save();

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $the_email = reset($mails);
    $this->assertEquals('bcc@example.com', $the_email['headers']['Bcc']);
  }

}
