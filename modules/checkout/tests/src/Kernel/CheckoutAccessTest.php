<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;
use Drupal\user\UserInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the checkout access for orders.
 *
 * @group commerce
 */
class CheckoutAccessTest extends CartKernelTestBase {

  use StoreCreationTrait;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The store to test against.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

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
    $this->createUser();
    $this->accessManager = $this->container->get('access_manager');
    $this->orderItemStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $product->save();
    $this->variation = $this->reloadEntity($variation);

    $this->store = $this->createStore();
  }

  /**
   * Tests that users need the `access checkout` permission.
   */
  public function testAccessCheckoutPermission() {
    $user_with_access = $this->createUser([], ['access checkout']);
    $user_without_access = $this->createUser([], []);

    $order = $this->createOrder($user_with_access);
    $request = $this->createRequest($order);
    $this->assertTrue($this->accessManager->checkRequest($request, $user_with_access));

    $order = $this->createOrder($user_without_access);
    $request = $this->createRequest($order);
    $this->assertFalse($this->accessManager->checkRequest($request, $user_without_access));
  }

  /**
   * Tests that only the order's owner can view its checkout.
   */
  public function testOwnerCheckoutAccess() {
    $user1 = $this->createUser([], ['access checkout']);
    $user2 = $this->createUser([], ['access checkout']);
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createOrder($user1);
    $request = $this->createRequest($order);
    $this->assertTrue($this->accessManager->checkRequest($request, $user1));
    $this->assertFalse($this->accessManager->checkRequest($request, $user2));
  }

  /**
   * Tests that canceled orders cannot enter checkout.
   */
  public function testCanceledOrderCheckout() {
    $user1 = $this->createUser([], ['access checkout']);
    $order = $this->createOrder($user1);
    $order->getState()->applyTransitionById('cancel');
    $request = $this->createRequest($order);
    $this->assertFalse($this->accessManager->checkRequest($request, $user1));
  }

  /**
   * Tests that an order must have items to enter checkout.
   */
  public function testOrderMustHaveItems() {
    $user1 = $this->createUser([], ['access checkout']);
    $order = $this->createOrder($user1);
    $order->setItems([]);
    $request = $this->createRequest($order);
    $this->assertFalse($this->accessManager->checkRequest($request, $user1));
  }

  /**
   * Creates a request for the order's checkout form.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $step
   *   The step.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  protected function createRequest(OrderInterface $order, $step = NULL) {
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $order->id(),
      'step' => $step,
    ]);
    $route_provider = $this->container->get('router.route_provider');
    $route = $route_provider->getRouteByName($url->getRouteName());

    $request = Request::create($url->toString());
    $request->attributes->add([
      RouteObjectInterface::ROUTE_OBJECT => $route,
      'commerce_order' => $order,
    ]);
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);

    return $request;
  }

  /**
   * Creates a sample order with one order item for provided user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The sample order.
   */
  protected function createOrder(UserInterface $user) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store->id(),
    ]);
    $order_item = $this->orderItemStorage->createFromPurchasableEntity($this->variation);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    return $order;
  }

}
