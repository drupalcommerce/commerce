<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Url;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the checkout order manager.
 *
 * @group commerce
 */
class CheckoutOrderManagerTest extends OrderKernelTestBase {

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('commerce_checkout');

    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $order = Order::create([
      'type' => 'default',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    $this->order = $order;

    $this->checkoutOrderManager = $this->container->get('commerce_checkout.checkout_order_manager');

    // Fake a request so that the current_route_match works.
    // @todo Remove this when CheckoutFlowBase stops using the route match.
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $order->id(),
    ]);
    $route_provider = $this->container->get('router.route_provider');
    $route = $route_provider->getRouteByName($url->getRouteName());
    $request = Request::create($url->toString());
    $request->attributes->add([
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'commerce_order' => $order,
    ]);
    $this->container->get('request_stack')->push($request);
  }

  /**
   * Tests getting the order's checkout flow.
   */
  public function testGetCheckoutFlow() {
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($this->order);
    $this->assertInstanceOf(CheckoutFlow::class, $checkout_flow);
    $this->assertEquals('default', $checkout_flow->id());

    $this->order->checkout_flow->target_id = 'deleted';
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($this->order);
    $this->assertInstanceOf(CheckoutFlow::class, $checkout_flow);
    $this->assertEquals('default', $checkout_flow->id());
  }

  /**
   * Tests getting the order's checkout step ID.
   */
  public function testGetCheckoutStepId() {
    // Empty requested step ID when no checkout step was set.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order);
    $this->assertEquals('login', $step_id);

    $this->order->set('checkout_step', 'review');
    // Empty requested step ID.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order);
    $this->assertEquals('review', $step_id);

    // Invalid requested step ID.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order, 'fake_step');
    $this->assertEquals('review', $step_id);

    // Requested step ID matches the current step ID.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order, 'review');
    $this->assertEquals('review', $step_id);

    // Requested step ID is before the current step ID.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order, 'order_information');
    $this->assertEquals('order_information', $step_id);

    // Requested step ID is after the current step ID.
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order, 'payment');
    $this->assertEquals('review', $step_id);

    // Non-complete requested step ID for a placed order.
    $this->order->state = 'validation';
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($this->order, 'payment');
    $this->assertEquals('complete', $step_id);
  }

}
