<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the coupon redeem checkout pane.
 *
 * @group commerce
 * @group commerce_promotion
 */
class CouponRedemptionPaneWithPaymentTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

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
    'commerce_checkout',
    'commerce_payment',
    'commerce_payment_example',
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
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'conditions' => [],
    ]);

    $coupon = $this->createEntity('commerce_promotion_coupon', [
      'code' => $this->getRandomGenerator()->word(8),
      'status' => TRUE,
    ]);
    $coupon->save();
    $this->promotion->get('coupons')->appendItem($coupon);
    $this->promotion->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'api_key' => '2342fewfsfs',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'redirect_method' => 'post',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();

    $profile = $this->createEntity('profile', [
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $this->adminUser->id(),
    ]);
    $payment_method1 = $this->createEntity('commerce_payment_method', [
      'uid' => $this->adminUser->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1111',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method1->setBillingProfile($profile);
    $payment_method1->save();
    $payment_method2 = $this->createEntity('commerce_payment_method', [
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '9999',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method2->setBillingProfile($profile);
    $payment_method2->save();
  }

  /**
   * Tests checkout with redeemed coupon.
   */
  public function testCheckoutWithPayment() {
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $existing_coupon */
    $existing_coupon = $this->promotion->get('coupons')->first()->entity;

    $this->getSession()->getPage()->fillField('Coupon code', $existing_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Redeem');
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('Coupon applied');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $radio_button = $this->getSession()->getPage()->findField('Visa ending in 9999');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$this->cart->id()]);
    $this->cart = $order_storage->load($this->cart->id());

    $this->assertEquals(new Price('899.10', 'USD'), $this->cart->getTotalPrice());
  }

  /**
   * Tests checkout with redeemed coupon.
   *
   * @group debug
   */
  public function testCheckoutWithPaymentSelectPaymentFirst() {
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $radio_button = $this->getSession()->getPage()->findField('Visa ending in 9999');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $this->createScreenshot();
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $existing_coupon */
    $existing_coupon = $this->promotion->get('coupons')->first()->entity;

    $this->getSession()->getPage()->fillField('Coupon code', $existing_coupon->getCode());
    $this->getSession()->getPage()->pressButton('Redeem');
    $this->waitForAjaxToFinish();
    $this->createScreenshot();
    $this->assertSession()->pageTextContains('Coupon applied');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order');
    $order_storage->resetCache([$this->cart->id()]);
    $this->cart = $order_storage->load($this->cart->id());

    $this->assertEquals(new Price('899.10', 'USD'), $this->cart->getTotalPrice());
  }

}
