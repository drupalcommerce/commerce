<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the coupon redemption checkout pane.
 *
 * @group commerce
 */
class CouponRedemptionPaneTest extends CommerceWebDriverTestBase {

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

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('999.00', 'USD'),
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

    $coupon = $this->createEntity('commerce_promotion_coupon', [
      'code' => $this->getRandomGenerator()->word(8),
      'status' => TRUE,
    ]);
    $coupon->save();
    $this->promotion->addCoupon($coupon);
    $this->promotion->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $offsite_gateway */
    $offsite_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $offsite_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $onsite_gateway */
    $onsite_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $onsite_gateway->save();

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
   * Tests redeeming a coupon using the coupon redemption pane.
   */
  public function testCouponRedemption() {
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);
    $checkout_url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->cart->id(),
    ]);

    $this->drupalGet($checkout_url);
    // Confirm that validation errors set by the form element are visible.
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Please provide a coupon code');

    // Valid coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldNotExists('Coupon code');
    $this->assertSession()->buttonNotExists('Apply coupon');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    // Coupon removal.
    $this->getSession()->getPage()->pressButton('Remove coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains($coupon->getCode());
    $this->assertSession()->fieldExists('Coupon code');
    $this->assertSession()->buttonExists('Apply coupon');
    $this->assertSession()->pageTextNotContains('-$99.90');
    $this->assertSession()->pageTextContains('$999');

    // Valid coupon.
    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldNotExists('Coupon code');
    $this->assertSession()->buttonNotExists('Apply coupon');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    // Coupon removal.
    $this->getSession()->getPage()->pressButton('Remove coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains($coupon->getCode());
    $this->assertSession()->fieldExists('Coupon code');
    $this->assertSession()->buttonExists('Apply coupon');
    $this->assertSession()->pageTextNotContains('-$99.90');
    $this->assertSession()->pageTextContains('$999');

    // Confirm that the order summary is refreshed when outside of the sidebar.
    $checkout_flow = CheckoutFlow::load('default');
    $configuration = $checkout_flow->get('configuration');
    $configuration['panes']['order_summary']['step'] = 'order_information';
    $checkout_flow->set('configuration', $configuration);
    $checkout_flow->save();

    $this->drupalGet($checkout_url);
    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldNotExists('Coupon code');
    $this->assertSession()->buttonNotExists('Apply coupon');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');
  }

  /**
   * Tests redeeming coupon on the cart form, with multiple coupons allowed.
   */
  public function testMultipleCouponRedemption() {
    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.coupon_redemption.allow_multiple', TRUE);
    $config->save();
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);

    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));
    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldExists('Coupon code');
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    $this->getSession()->getPage()->pressButton('Remove coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('$999.00');
  }

  /**
   * Tests checkout with a redeemed coupon.
   */
  public function testCheckout() {
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->getSession()->getPage()->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->pageTextContains('-$99.90');
    $this->assertSession()->pageTextContains('$899.10');

    // Ensure that the payment method ajax works with the coupon ajax.
    $radio_button = $this->getSession()->getPage()->findField('Visa ending in 9999');
    $radio_button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains($coupon->getCode());
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
   * Tests checkout using the main submit button instead of 'Apply coupon'.
   */
  public function testCheckoutWithMainSubmit() {
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));

    $this->getSession()->getPage()->fillField('Coupon code', $coupon->getCode());
    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Visa ending in 9999');
    $this->assertSession()->pageTextContains($coupon->getCode());
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
   * Tests that adding/removing coupons does not submit other panes.
   */
  public function testCheckoutSubmit() {
    // Start checkout, and enter billing information.
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id()]));
    $this->getSession()->getPage()->findField('Example')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');

    // Go back and edit the billing information, but don't submit it.
    $this->getSession()->getPage()->clickLink('Go back');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $address_prefix = 'payment_information[billing_information][address][0][address]';
    $this->getSession()->getPage()->fillField($address_prefix . '[given_name]', 'John');
    $this->getSession()->getPage()->fillField($address_prefix . '[family_name]', 'Smith');

    // Add a coupon.
    $coupons = $this->promotion->getCoupons();
    $coupon = reset($coupons);
    $page = $this->getSession()->getPage();
    $page->fillField('Coupon code', $coupon->getCode());
    $page->pressButton('Apply coupon');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($coupon->getCode());
    $this->assertSession()->fieldNotExists('Coupon code');
    $this->assertSession()->buttonNotExists('Apply coupon');

    // Refresh the page and ensure the billing information hasn't been modified.
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => $this->cart->id(), 'step' => 'order_information']));
    $page = $this->getSession()->getPage();
    $this->assertStringContainsString('Johnny Appleseed', $page->find('css', 'p.address')->getText());
  }

}
