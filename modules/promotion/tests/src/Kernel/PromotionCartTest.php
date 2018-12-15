<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the integration between promotions and carts.
 *
 * @group commerce
 */
class PromotionCartTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

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
    $this->installCommerceCart();
  }

  /**
   * Tests adding a product with a promotion to the cart.
   */
  public function testPromotionCart() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
    ]);
    $product->save();

    $promotion = Promotion::create([
      'name' => 'Promotion test',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();

    $user = $this->createUser();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart_order */
    $cart = $this->cartProvider->createCart('default', $this->store, $user);
    $this->cartManager->addEntity($cart, $variation);

    $this->assertEquals(1, count($cart->collectAdjustments()));
    $this->assertEquals(new Price('9.00', 'USD'), $cart->getTotalPrice());

    // Disable the promotion.
    $promotion->setEnabled(FALSE);
    $promotion->save();
    $this->container->get('commerce_order.order_refresh')->refresh($cart);
    $this->assertEmpty($cart->getAdjustments());
    $this->assertEquals(new Price('10.00', 'USD'), $cart->getTotalPrice());
  }

}
