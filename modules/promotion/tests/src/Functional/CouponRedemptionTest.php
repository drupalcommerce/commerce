<?php

namespace Drupal\Tests\commerce_promotion\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the coupon redemption form element.
 *
 * @group commerce
 */
class CouponRedemptionTest extends CommerceBrowserTestBase {

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * The promotion for testing.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'commerce_cart',
    'commerce_promotion',
    'commerce_promotion_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cart = $this->container->get('commerce_cart.cart_provider')->createCart('default', $this->store, $this->adminUser);
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');

    // Create a product variation.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $this->cartManager->addEntity($this->cart, $this->variation);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);

    // Starts now, enabled. No end time.
    $this->promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Promotion (with coupon)',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);

    $coupon = $this->createEntity('commerce_promotion_coupon', [
      'code' => $this->randomString(),
      'status' => TRUE,
    ]);
    $coupon->save();
    $this->promotion->get('coupons')->appendItem($coupon);
    $this->promotion->save();
  }

  /**
   * Tests redeeming coupon on the cart form.
   *
   * @see commerce_promotion_test_form_views_form_commerce_cart_form_default_alter
   */
  public function testCouponRedemption() {
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $existing_coupon */
    $existing_coupon = $this->promotion->get('coupons')->first()->entity;

    $this->assertSession()->pageTextContains('Enter your promotion code to redeem a discount.');
    $this->assertSession()->elementTextNotContains('css', '.order-total-line', 'Discount');

    // Test entering an invalid coupon.
    $this->getSession()->getPage()->fillField('Promotion code', $this->randomString());
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertSession()->pageTextContains('Coupon is invalid');

    $this->getSession()->getPage()->fillField('coupons[code]', $existing_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply');

    $this->assertSession()->pageTextContains('Coupon applied');
    // The view is processed before the coupon element, so it
    // won't reflect the updated order until the page reloads.
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->assertSession()->pageTextContains('-$99.90');

    $this->assertSession()->fieldNotExists('coupons[code]');
    $this->assertSession()->buttonNotExists('Apply');
    $this->getSession()->getPage()->pressButton('Remove promotion');

    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->assertSession()->pageTextNotContains('-$99.90');
    $this->assertSession()->fieldExists('coupons[code]');
    $this->assertSession()->buttonExists('Apply');
  }

}
