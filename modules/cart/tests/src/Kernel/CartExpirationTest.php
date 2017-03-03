<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests cart expiration.
 *
 * @group commerce
 */
class CartExpirationTest extends CommerceKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManager
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProvider
   */
  protected $cartProvider;

  /**
   * The order storage.
   *
   * @var \Drupal\commerce_order\OrderStorage
   */
  protected $orderStorage;

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

    $this->installEntitySchema('commerce_order');
    $this->installConfig(['commerce_order']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
    $this->orderStorage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
  }

  /**
   * Install commerce cart.
   *
   * Due to issues with hook_entity_bundle_create, we need to run this manually
   * and cannot add commerce_cart to the $modules property.
   *
   * @see https://www.drupal.org/node/2711645
   *
   * @todo patch core so it doesn't explode in Kernel tests.
   */
  protected function installCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');
    $this->container->get('entity.definition_update_manager')->applyUpdates();
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
  }

  /**
   * Sets an expiration time for default order type, verifies it works.
   */
  public function testExpiration() {
    $this->installCommerceCart();

    // Set expiration to 3 days.
    $order_type = OrderType::load('default');
    $order_type->setThirdPartySetting('commerce_cart', 'cart_expiration', 3);
    $order_type->save();

    /** @var \Drupal\commerce\TimeInterface $time */
    $time = \Drupal::service('commerce.time');
    $four_days_ago = $time->getRequestTime() - (86400 * 4);
    $two_days_ago = $time->getRequestTime() - (86400 * 2);
    $one_day_ago = $time->getRequestTime() - 86400;

    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart1 */
    $cart1 = $this->orderStorage->create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart1->save();
    $cart2 = $this->orderStorage->create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $four_days_ago,
      'changed' => $four_days_ago,
    ]);
    $cart2->save();
    $this->orderStorage->create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $two_days_ago,
      'changed' => $two_days_ago,
    ])->save();
    $this->orderStorage->create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
      'created' => $one_day_ago,
      'changed' => $one_day_ago,
    ])->save();
    $this->orderStorage->create([
      'type' => $order_type->id(),
      'store_id' => $this->store->id(),
      'uid' => $this->createUser()->id(),
      'cart' => TRUE,
    ])->save();

    // Setting the `changed` attribute doesn't work in save. Manually change.
    $count = db_update('commerce_order')
      ->fields(['changed' => $four_days_ago])
      ->condition('order_id', [$cart1->id(), $cart2->id()], 'IN')
      ->execute();
    $this->assertEquals(2, $count);

    $this->container->get('cron')->run();

    $orders = $this->orderStorage->loadMultiple();
    $this->assertEquals(3, count($orders));
    $this->assertNull($this->orderStorage->load($cart1->id()));
    $this->assertNull($this->orderStorage->load($cart2->id()));
  }

}
