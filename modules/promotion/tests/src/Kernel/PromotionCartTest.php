<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_cart\Kernel\CartManagerTestTrait;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests promotion offers.
 *
 * @group commerce
 */
class PromotionCartTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * The offer manager.
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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'path',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_product',
      'commerce_promotion',
    ]);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

  }

  /**
   * Tests order percentage off.
   */
  public function testPromotionCart() {
    $this->installCommerceCart();

    // Create a product variation.
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => '40.00',
        'currency_code' => 'USD',
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation],
    ]);
    $this->reloadEntity($variation);
    $variation->save();

    // Starts now, enabled. No end time.
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Promotion test',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
    ]);
    $promotion->save();

    $user = $this->createUser();
    $user = $this->reloadEntity($user);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart_order */
    $cart_order = $this->cartProvider->createCart('default', $this->store, $user);

    // Use addOrderItem so the total is calculated.
    $this->cartManager->addEntity($cart_order, $variation);

    $this->assertEquals(1, count($cart_order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $cart_order->getTotalPrice());

    // Disable the promotion.
    $promotion->setEnabled(FALSE);
    $promotion->save();
    $this->container->get('commerce_order.order_refresh')->refresh($cart_order);
    $this->assertEmpty($cart_order->getAdjustments());
    $this->assertEquals(new Price('40.00', 'USD'), $cart_order->getTotalPrice());
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
