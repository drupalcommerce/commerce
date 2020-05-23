<?php

namespace Drupal\Tests\commerce_order\Kernel\Mail;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the sending of order receipt emails.
 *
 * @coversDefaultClass \Drupal\commerce_order\Mail\OrderReceiptMail
 * @group commerce
 */
class OrderReceiptMailTest extends OrderKernelTestBase {

  use AssertMailTrait;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The order receipt.
   *
   * @var \Drupal\commerce_order\Mail\OrderReceiptMailInterface
   */
  protected $orderReceiptMail;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->createUser([
      'mail' => 'customer@example.com',
      'preferred_langcode' => 'en',
    ]);

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
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();
    $order = Order::create([
      'type' => 'default',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '2017/01',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [$order_item1],
      'state' => 'completed',
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);

    $this->orderReceiptMail = $this->container->get('commerce_order.order_receipt_mail');
  }

  /**
   * @covers ::send
   */
  public function testSend() {
    $this->orderReceiptMail->send($this->order);

    $emails = $this->getMails();
    $this->assertCount(1, $emails);
    $email = end($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $email['headers']['Content-Transfer-Encoding']);
    $this->assertEquals($this->order->getStore()->getEmail(), $email['from']);
    $this->assertEquals('customer@example.com', $email['to']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals('Order #2017/01 confirmed', $email['subject']);
    $this->assertStringContainsString('Thank you for your order!', $email['body']);
    $this->assertStringContainsString('Pabst Blue Ribbon Dr', $email['body']);
    $this->assertEquals('en', $email['params']['langcode']);
    $this->assertEquals($this->order, $email['params']['order']);

    $this->orderReceiptMail->send($this->order, 'custom@example.com', 'store@example.com');

    $emails = $this->getMails();
    $this->assertCount(2, $emails);
    $email = end($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $email['headers']['Content-Transfer-Encoding']);
    $this->assertEquals($this->order->getStore()->getEmail(), $email['from']);
    $this->assertEquals('custom@example.com', $email['to']);
    $this->assertEquals('store@example.com', $email['headers']['Bcc']);
    $this->assertEquals('Order #2017/01 confirmed', $email['subject']);
    $this->assertStringContainsString('Thank you for your order!', $email['body']);
    $this->assertStringContainsString('Pabst Blue Ribbon Dr', $email['body']);
    $this->assertEquals('en', $email['params']['langcode']);
    $this->assertEquals($this->order, $email['params']['order']);
  }

}
