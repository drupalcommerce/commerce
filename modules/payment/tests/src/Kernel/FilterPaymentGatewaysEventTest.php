<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the FilterPaymentGatewaysEvent.
 *
 * @group commerce
 */
class FilterPaymentGatewaysEventTest extends OrderKernelTestBase {

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface
   */
  protected $storage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_payment_example',
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('commerce_payment');

    $this->storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment_gateway');
  }

  /**
   * Tests that the proper gateway is filtered out.
   */
  public function testEvent() {
    $payment_gateway_example = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
      'weight' => 1,
    ]);
    $payment_gateway_example->save();
    $payment_gateway_filtered = PaymentGateway::create([
      'id' => 'example_filtered',
      'label' => 'Example (Filtered)',
      'plugin' => 'example_onsite',
      'weight' => 2,
    ]);
    $payment_gateway_filtered->save();

    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    $user = $this->createUser();
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

    $available_payment_gateways = $this->storage->loadMultipleForOrder($order);
    $this->assertEquals(2, count($available_payment_gateways));
    $payment_gateway = array_shift($available_payment_gateways);
    $this->assertEquals($payment_gateway_example->label(), $payment_gateway->label());
    $payment_gateway = array_shift($available_payment_gateways);
    $this->assertEquals($payment_gateway_filtered->label(), $payment_gateway->label());

    $order->setData('excluded_gateways', [$payment_gateway_filtered->id()]);

    $available_payment_gateways = $this->storage->loadMultipleForOrder($order);
    $this->assertEquals(1, count($available_payment_gateways));
    $payment_gateway = array_shift($available_payment_gateways);
    $this->assertEquals($payment_gateway_example->label(), $payment_gateway->label());
  }

}
