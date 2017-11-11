<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the chain checkout flow resolver.
 *
 * @group commerce
 */
class ChainCheckoutFlowResolverTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'options',
    'entity',
    'entity_reference_revisions',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_checkout');
  }

  /**
   * Tests resolving the checkout flow.
   */
  public function testCheckoutFlowResolution() {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $order = Order::create([
      'type' => 'default',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();

    $resolver = $this->container->get('commerce_checkout.chain_checkout_flow_resolver');
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $resolver->resolve($order);

    $this->assertEquals('default', $checkout_flow->id());
  }

}
