<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests sending of order receipt emails using MailSystem mail theme setting.
 *
 * @requires module mailsystem
 * @group commerce
 */
class OrderReceiptThemeTest extends CommerceKernelTestBase {

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
    'system',
    'filter',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'mailsystem',
    'mailsystem_test',
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
    $this->installConfig(['commerce_product', 'commerce_order', 'mailsystem']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    \Drupal::service('theme_handler')->install(['commerce_order_test_theme']);

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
      'order_number' => '2017/01',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [$order_item1],
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests the order receipt without a custom theme.
   */
  public function testOrderReceiptDefault() {
    $mailsystem_config = $this->config('mailsystem.settings');
    $mailsystem_config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->save();

    $this->order->getState()->applyTransitionById('place');
    $this->order->save();
    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $email = reset($mails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $email['headers']['Content-Transfer-Encoding']);
    $this->assertEquals('Order #2017/01 confirmed', $email['subject']);
    $this->assertNotContains('Commerce order test theme', $email['body']);
  }

  /**
   * Tests the order receipt with a custom theme.
   */
  public function testOrderReceiptThemed() {
    $mailsystem_config = $this->config('mailsystem.settings');
    $mailsystem_config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->set('theme', 'commerce_order_test_theme')
      ->save();

    $this->order->getState()->applyTransitionById('place');
    $this->order->save();
    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $email = reset($mails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('8Bit', $email['headers']['Content-Transfer-Encoding']);
    $this->assertEquals('Order #2017/01 confirmed', $email['subject']);
    $this->assertContains('Commerce order test theme', $email['body']);
    $this->assertTrue(strpos($email['body'], 'Commerce order test theme') !== FALSE);
  }

}
