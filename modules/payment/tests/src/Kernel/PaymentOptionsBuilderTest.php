<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the payment options builder.
 *
 * @coversDefaultClass \Drupal\commerce_payment\PaymentOptionsBuilder
 *
 * @group commerce
 */
class PaymentOptionsBuilderTest extends CommerceKernelTestBase {

  /**
   * The payment options builder.
   *
   * @var \Drupal\commerce_payment\PaymentOptionsBuilderInterface
   */
  protected $paymentOptionsBuilder;

  /**
   * The sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    $this->paymentOptionsBuilder = $this->container->get('commerce_payment.options_builder');

    $user = $this->createUser();
    $another_user = $this->createUser();

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $payment_gateway->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'cash_on_delivery',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Cash on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
      'weight' => 10,
    ]);
    $payment_gateway->save();

    // A manual gateway with a condition that won't be satisfied, to ensure
    // that it's not offered to the user.
    $payment_gateway = PaymentGateway::create([
      'id' => 'card_on_delivery',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Card on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
      'conditions' => [
        [
          'plugin' => 'order_total_price',
          'configuration' => [
            'operator' => '>',
            'amount' => [
              'number' => '99.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'weight' => 10,
    ]);
    $payment_gateway->save();

    $profile = Profile::create([
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
      'uid' => $user->id(),
    ]);
    $profile->save();

    $payment_method = PaymentMethod::create([
      'uid' => $user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1111',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();

    // Create a payment method for $another_user as well, to confirm that it's
    // not offered to the first user.
    $payment_method = PaymentMethod::create([
      'uid' => $another_user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '1112',
      'billing_profile' => $profile,
      'reusable' => TRUE,
      'expires' => strtotime('2028/03/24'),
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->save();

    $order_payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'onsite',
      'card_type' => 'visa',
      'card_number' => '9999',
      'reusable' => FALSE,
    ]);
    $order_payment_method->save();

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);
    $order_item->save();

    $this->order = Order::create([
      'uid' => $user->id(),
      'type' => 'default',
      'state' => 'draft',
      'order_items' => [$order_item],
      'payment_gateway' => 'onsite',
      'payment_method' => $order_payment_method,
      'store_id' => $this->store,
    ]);
    $this->order->save();
  }

  /**
   * Tests building options for all available gateways.
   *
   * @covers ::buildOptions
   */
  public function testBuildOptions() {
    $options = $this->paymentOptionsBuilder->buildOptions($this->order);
    /** @var \Drupal\commerce_payment\PaymentOption[] $options */
    $options = array_values($options);
    $this->assertCount(5, $options);

    // Stored payment methods.
    $this->assertEquals('1', $options[0]->getId());
    $this->assertEquals('Visa ending in 1111', $options[0]->getLabel());
    $this->assertEquals('onsite', $options[0]->getPaymentGatewayId());
    $this->assertEquals('1', $options[0]->getPaymentMethodId());
    $this->assertNull($options[0]->getPaymentMethodTypeId());

    // Order payment method.
    $this->assertEquals('3', $options[1]->getId());
    $this->assertEquals('Visa ending in 9999', $options[1]->getLabel());
    $this->assertEquals('onsite', $options[1]->getPaymentGatewayId());
    $this->assertEquals('3', $options[1]->getPaymentMethodId());
    $this->assertNull($options[1]->getPaymentMethodTypeId());

    // Add new payment method.
    $this->assertEquals('new--credit_card--onsite', $options[2]->getId());
    $this->assertEquals('Credit card', $options[2]->getLabel());
    $this->assertEquals('onsite', $options[2]->getPaymentGatewayId());
    $this->assertNull($options[2]->getPaymentMethodId());
    $this->assertEquals('credit_card', $options[2]->getPaymentMethodTypeId());

    // Offsite gateways.
    $this->assertEquals('offsite', $options[3]->getId());
    $this->assertEquals('Example', $options[3]->getLabel());
    $this->assertEquals('offsite', $options[3]->getPaymentGatewayId());
    $this->assertNull($options[3]->getPaymentMethodId());
    $this->assertNull($options[3]->getPaymentMethodTypeId());

    // Manual gateways.
    $this->assertEquals('cash_on_delivery', $options[4]->getId());
    $this->assertEquals('Cash on delivery', $options[4]->getLabel());
    $this->assertEquals('cash_on_delivery', $options[4]->getPaymentGatewayId());
    $this->assertNull($options[4]->getPaymentMethodId());
    $this->assertNull($options[4]->getPaymentMethodTypeId());
  }

  /**
   * Tests building options for two different on-site gateways.
   *
   * Confirms that the payment gateway list can be restricted, and that
   * multiple on-site gateways get unique "add" option labels.
   *
   * @covers ::buildOptions
   */
  public function testBuildOptionsWithTwoOnsiteGateways() {
    $first_payment_gateway = PaymentGateway::create([
      'id' => 'first_onsite',
      'label' => 'First (On-site)',
      'plugin' => 'example_onsite',
    ]);
    $second_payment_gateway = PaymentGateway::create([
      'id' => 'second_onsite',
      'label' => 'Second (On-site)',
      'plugin' => 'test_onsite',
    ]);
    $second_payment_gateway->save();
    $payment_gateways = [$first_payment_gateway, $second_payment_gateway];
    $options = $this->paymentOptionsBuilder->buildOptions($this->order, $payment_gateways);
    /** @var \Drupal\commerce_payment\PaymentOption[] $options */
    $options = array_values($options);
    $this->assertCount(2, $options);

    $this->assertEquals('new--credit_card--first_onsite', $options[0]->getId());
    $this->assertEquals('Credit card (Example)', $options[0]->getLabel());
    $this->assertEquals('first_onsite', $options[0]->getPaymentGatewayId());
    $this->assertNull($options[0]->getPaymentMethodId());
    $this->assertEquals('credit_card', $options[0]->getPaymentMethodTypeId());

    $this->assertEquals('new--credit_card--second_onsite', $options[1]->getId());
    $this->assertEquals('Credit card (Test)', $options[1]->getLabel());
    $this->assertEquals('second_onsite', $options[1]->getPaymentGatewayId());
    $this->assertNull($options[1]->getPaymentMethodId());
    $this->assertEquals('credit_card', $options[1]->getPaymentMethodTypeId());
  }

  /**
   * Tests selecting the default option.
   *
   * @covers ::selectDefaultOption
   */
  public function testSelectDefaultOption() {
    $options = $this->paymentOptionsBuilder->buildOptions($this->order);

    // The order payment method is selected first.
    $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    $this->assertEquals($options[3], $default_option);

    // The order payment gateway is selected second.
    $this->order->set('payment_method', NULL);
    $this->order->set('payment_gateway', 'cash_on_delivery');
    $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    $this->assertEquals($options['cash_on_delivery'], $default_option);

    // Finally, the method falls back to the first option.
    $this->order->set('payment_gateway', NULL);
    $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    $this->assertEquals(reset($options), $default_option);

    // Non-available order payment method is ignored.
    $this->order->set('payment_method', '2');
    $this->order->set('payment_gateway', 'onsite');
    $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    $this->assertEquals(reset($options), $default_option);

    // Non-available order payment gateway is ignored.
    $this->order->set('payment_method', NULL);
    $this->order->set('payment_gateway', 'card_on_delivery');
    $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    $this->assertEquals(reset($options), $default_option);
  }

}
