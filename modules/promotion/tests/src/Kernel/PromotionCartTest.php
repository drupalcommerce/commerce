<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;

/**
 * Tests the integration between promotions and carts.
 *
 * @group commerce
 */
class PromotionCartTest extends CartKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installConfig(['commerce_promotion']);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
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
