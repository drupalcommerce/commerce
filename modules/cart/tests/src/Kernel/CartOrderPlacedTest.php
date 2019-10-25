<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the unsetting of the cart flag when order is placed.
 *
 * @covers \Drupal\commerce_cart\CartProvider::finalizeCart()
 * @group commerce
 */
class CartOrderPlacedTest extends OrderKernelTestBase {

  use CartManagerTestTrait;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installCommerceCart();
    $this->createUser();

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

    // Create a user to use for orders.
    $this->user = $this->createUser();
  }

  /**
   * Tests that a draft order is no longer a cart once placed.
   */
  public function testCartOrderPlaced() {
    $this->store = $this->createStore();
    $cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $this->user);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
    $this->cartManager->addEntity($cart_order, $this->variation);

    $this->assertNotEmpty($cart_order->cart->value);

    $cart_order->getState()->applyTransitionById('place');
    $cart_order->save();

    $cart_order = $this->reloadEntity($cart_order);
    $this->assertEmpty($cart_order->cart->value);

    // We should be able to create a new cart and not get an exception.
    $new_cart_order = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $this->user);
    $this->assertNotEquals($cart_order->id(), $new_cart_order->id());
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
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
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
