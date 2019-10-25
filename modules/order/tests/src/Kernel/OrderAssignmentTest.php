<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\profile\Entity\Profile;

/**
 * Tests the order assignment service.
 *
 * @coversDefaultClass \Drupal\commerce_order\OrderAssignment
 * @group commerce
 */
class OrderAssignmentTest extends OrderKernelTestBase {

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
    'commerce_payment',
    'commerce_payment_example',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig(['commerce_payment']);
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

    $billing_profile = Profile::create([
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
    $billing_profile->save();
    $billing_profile = $this->reloadEntity($billing_profile);

    $payment_method_profile = $billing_profile->createDuplicate();
    $payment_method_profile->setData('copy_to_address_book', TRUE);
    $payment_method_profile->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->setPluginConfiguration([
      'api_key' => '2342fewfsfs',
      'mode' => 'test',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'payment_gateway_mode' => 'test',
      'billing_profile' => $payment_method_profile,
    ]);
    $payment_method->save();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($variation);
    $order_item->save();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'order_number' => '6',
      'store_id' => $this->store->id(),
      'uid' => $this->user->id(),
      'mail' => $this->user->getEmail(),
      'ip_address' => '127.0.0.1',
      'billing_profile' => $billing_profile,
      'payment_method' => $payment_method,
      'order_items' => [$order_item],
      'state' => 'draft',
    ]);
    $order->save();
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
    $second_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->orderAssignment->assignMultiple([$this->order], $second_user);

    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals($second_user->id(), $this->order->getCustomerId());
    $this->assertEquals($second_user->getEmail(), $this->order->getEmail());

    $billing_profile = $this->order->getBillingProfile();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->order->get('payment_method')->entity;
    $payment_method_profile = $payment_method->getBillingProfile();
    // Confirm that the billing profile was not reassigned.
    $this->assertEquals(0, $billing_profile->getOwnerId());
    // Confirm that the payment method was reassigned.
    $this->assertEquals($second_user->id(), $payment_method->getOwnerId());
    // Confirm that the payment method profile was copied to the address book.
    $this->assertEmpty($payment_method_profile->getData('copy_to_address_book'));
    $this->assertNotEmpty($payment_method_profile->getData('address_book_profile_id'));
    // Confirm that the new ID was also copied to the billing profile.
    $this->assertNotEmpty($billing_profile->getData('address_book_profile_id'));

    $third_user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $this->orderAssignment->assignMultiple([$this->order], $third_user);

    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals($third_user->id(), $this->order->getCustomerId());
    $this->assertEquals($third_user->getEmail(), $this->order->getEmail());

    $billing_profile = $this->reloadEntity($billing_profile);
    $payment_method = $this->reloadEntity($payment_method);
    // Confirm that the billing profile and payment method were not reassigned.
    $this->assertEquals(0, $billing_profile->getOwnerId());
    $this->assertEquals($second_user->id(), $payment_method->getOwnerId());
  }

}
