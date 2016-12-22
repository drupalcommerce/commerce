<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the checkout access for orders.
 */
class CheckoutAccessTest extends EntityKernelTestBase {

  use StoreCreationTrait;

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
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

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

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_store');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_checkout');
    $this->createUser();
    $this->orderItemStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    // Create a product variation.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $this->reloadEntity($this->variation);
    $this->variation->save();

    $this->store = $this->createStore();
  }

  /**
   * Tests that users need `access checkout` permission.
   */
  public function testAccessCheckoutPermission() {
    $this->enableCommerceCart();

    $user_with_access = $this->createUser([], ['access checkout']);
    $user_without_access = $this->createUser([], []);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createSampleOrder($user_with_access);
    $request = $this->createCheckoutRequest($order);
    $this->assertTrue($this->container->get('access_manager')->checkRequest($request, $user_with_access));

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order =$this->createSampleOrder($user_without_access);
    $request = $this->createCheckoutRequest($order);
    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $user_without_access));
  }

  /**
   * Tests that only an order owner can view its checkout.
   */
  public function testOwnerCheckoutAccess() {
    $this->enableCommerceCart();

    $anon = User::getAnonymousUser();
    $user1 = $this->createUser([], ['access checkout']);
    $user2 = $this->createUser([], ['access checkout']);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createSampleOrder($user1);
    $request = $this->createCheckoutRequest($order);

    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $anon));
    $this->assertTrue($this->container->get('access_manager')->checkRequest($request, $user1));
    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $user2));
  }

  /**
   * Tests that only draft orders can visit checkout.
   */
  public function testOnlyDraftOrderCheckout() {
    $this->enableCommerceCart();
    $user1 = $this->createUser([], ['access checkout']);

    // Canceled orders cannot enter checkout.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createSampleOrder($user1);
    $order->getState()->applyTransition($order->getState()->getTransitions()['cancel']);
    $request = $this->createCheckoutRequest($order);
    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $user1));

    // Placed orders cannot enter checkout.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createSampleOrder($user1);
    $order->getState()->applyTransition($order->getState()->getTransitions()['place']);
    $request = $this->createCheckoutRequest($order);
    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $user1));
  }

  public function testOrderMustHaveitems() {
    $this->enableCommerceCart();
    $user1 = $this->createUser([], ['access checkout']);

    // Canceled orders cannot enter checkout.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createSampleOrder($user1);
    $order->setItems([]);
    $request = $this->createCheckoutRequest($order);
    $this->assertFalse($this->container->get('access_manager')->checkRequest($request, $user1));
  }

  /**
   * Creates a Request object for an order's checkout path.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $step
   *   The step.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  protected function createCheckoutRequest(OrderInterface $order, $step = NULL) {
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $order->id(),
      'step' => $step,
    ]);

    // Push the request to the request stack so `current_route_match` works.
    $request = Request::create($url->toString());
    $request->attributes->add([
      RouteObjectInterface::ROUTE_OBJECT => $this->container->get('router.route_provider')->getRouteByName($url->getRouteName()),
      'commerce_order' => $order
    ]);
    $this->container->get('request_stack')->push($request);
    return $request;
  }

  /**
   * Creates sample order with one order item for provided user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The sample order.
   */
  protected function createSampleOrder(UserInterface $user) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createEntity('commerce_order', [
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

  /**
   * Due to issues with hook_entity_bundle_create, we need to run this here
   * and can't put commerce_cart in $modules.
   * See https://www.drupal.org/node/2711645
   * @todo patch core so it doesn't explode in Kernel tests.
   * @todo remove Cart dependency in Checkout
   */
  protected function enableCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');
    $this->container->get('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
