<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the coupon redemption form element.
 *
 * @group commerce
 */
class CouponRedemptionElementTest extends CommerceWebDriverTestBase {

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
   * The promotion.
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

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $this->cartManager->addOrderItem($this->cart, $order_item);

    // Starts now, enabled. No end time.
    $this->promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Promotion (with coupon)',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'conditions' => [],
    ]);

    $first_coupon = $this->createEntity('commerce_promotion_coupon', [
      'code' => $this->getRandomGenerator()->word(8),
      'status' => TRUE,
    ]);
    $first_coupon->save();
    $second_coupon = $this->createEntity('commerce_promotion_coupon', [
      'code' => $this->getRandomGenerator()->word(8),
      'status' => TRUE,
    ]);
    $second_coupon->save();
    $this->promotion->setCoupons([$first_coupon, $second_coupon]);
    $this->promotion->save();
  }

  /**
   * Tests redeeming a single coupon.
   *
   * @see commerce_promotion_test_form_views_form_commerce_cart_form_default_alter()
   */
  public function testSingleCouponRedemption() {
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);

    $this->drupalGet(Url::fromRoute('commerce_cart.page', [], ['query' => ['coupon_cardinality' => 1]]));
    // Empty coupon.
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('Please provide a coupon code');

    // Non-existent coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $this->randomString());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('The provided coupon code is invalid');

    // Valid coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldNotExists('Coupon code');
    $this->assertSession()->buttonNotExists('Apply coupon');

    // Coupon removal.
    $this->getSession()->getPage()->pressButton('Remove coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextNotContains($coupon->getCode());
    $this->assertSession()->fieldExists('Coupon code');
    $this->assertSession()->buttonExists('Apply coupon');
  }

  /**
   * Tests redeeming coupon on the cart form, with multiple coupons allowed.
   *
   * @see commerce_promotion_test_form_views_form_commerce_cart_form_default_alter()
   */
  public function testMultipleCouponRedemption() {
    $coupons = $this->promotion->getCoupons();
    $first_coupon = reset($coupons);
    $second_coupon = end($coupons);

    $this->drupalGet(Url::fromRoute('commerce_cart.page', [], ['query' => ['coupon_cardinality' => 2]]));
    // First coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $first_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains($first_coupon->getCode());
    $this->assertSession()->fieldExists('Coupon code');
    // The coupon code input field needs to be cleared.
    $this->assertSession()->fieldValueNotEquals('Coupon code', $first_coupon->getCode());

    // First coupon, applied for the second time.
    $this->getSession()->getPage()->fillField('Coupon code', $first_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextNotContains('The provided coupon code is invalid');
    $this->assertSession()->pageTextContains($first_coupon->getCode());

    // Second coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $second_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains($first_coupon->getCode());
    $this->assertSession()->pageTextContains($second_coupon->getCode());

    // Second coupon removal.
    $this->getSession()->getPage()->pressButton('remove_coupon_1');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextNotContains($second_coupon->getCode());
    $this->assertSession()->pageTextContains($first_coupon->getCode());

    // First coupon removal.
    $this->getSession()->getPage()->pressButton('remove_coupon_0');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextNotContains($second_coupon->getCode());
    $this->assertSession()->pageTextNotContains($first_coupon->getCode());
  }

}
