<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests saving order data before and after saving the payment.
 *
 * @link https://www.drupal.org/project/commerce/issues/3011667
 *
 * @group commerce
 */
class OffsiteOrderDataTest extends CommerceWebDriverTestBase {

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'test_offsite',
      'configuration' => [
        // PayPal uses GET, follow its pattern for this test.
        'redirect_method' => 'get',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $payment_gateway->save();
  }

  /**
   * Tests the order data saving.
   *
   * @dataProvider saveDataProvider
   */
  public function testSave($when_to_save) {
    $state = $this->container->get('state');
    $state->set('offsite_order_data_test_save', $when_to_save);

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);
    $this->assertFalse($order->isLocked());
    $this->assertTrue($order->isPaid());
    $this->assertTrue($order->getData('paid_event_dispatched'));
    // Confirm that gateway data was set and preserved.
    $this->assertEquals($order->getData('test_offsite'), [
      'test' => TRUE,
    ]);
  }

  /**
   * Data provider for ::testSave.
   *
   * @return array
   *   A list of testSave function arguments.
   */
  public function saveDataProvider() {
    return [
      ['before'],
      ['after'],
    ];
  }

}
