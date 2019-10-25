<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the payment method storage.
 *
 * @group commerce
 */
class PaymentMethodStorageTest extends OrderKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * The payment method storage.
   *
   * @var \Drupal\commerce_payment\PaymentMethodStorageInterface
   */
  protected $storage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_payment');

    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->setPluginConfiguration([
      'api_key' => '2342fewfsfs',
      'mode' => 'test',
      'payment_method_types' => ['credit_card'],
    ]);
    $payment_gateway->save();
    $this->paymentGateway = $this->reloadEntity($payment_gateway);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment_method');
  }

  /**
   * Tests loading reusable payment methods.
   */
  public function testLoadReusable() {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method_expired */
    $payment_method_expired = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'payment_gateway_mode' => 'test',
      // Sat, 16 Jan 2016.
      'expires' => '1452902400',
      'uid' => $this->user->id(),
    ]);
    $payment_method_expired->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method_active */
    $payment_method_active = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'payment_gateway_mode' => 'test',
      // Thu, 16 Jan 2020.
      'expires' => '1579132800',
      'uid' => $this->user->id(),
    ]);
    $payment_method_active->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method_unlimited */
    $payment_method_unlimited = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'payment_gateway_mode' => 'test',
      'expires' => 0,
      'uid' => $this->user->id(),
    ]);
    $payment_method_unlimited->save();
    // Confirm that the expired payment method was not loaded.
    $reusable_payment_methods = $this->storage->loadReusable($this->user, $this->paymentGateway);
    $this->assertEquals([$payment_method_unlimited->id(), $payment_method_active->id()], array_keys($reusable_payment_methods));

    // Confirm that anonymous users cannot have reusable payment methods.
    $payment_method_active->setOwnerId(0);
    $payment_method_active->save();
    $payment_method_unlimited->setOwnerId(0);
    $payment_method_unlimited->save();
    $this->assertEmpty($this->storage->loadReusable(User::getAnonymousUser(), $this->paymentGateway));
    $this->assertEmpty($this->storage->loadReusable($this->user, $this->paymentGateway));

    // Changing the gateway from test to live should cause all of the testing
    // payment methods to be ignored.
    $payment_gateway_configuration = $this->paymentGateway->getPluginConfiguration();
    $payment_gateway_configuration['mode'] = 'live';
    $this->paymentGateway->setPluginConfiguration($payment_gateway_configuration);
    $this->paymentGateway->save();
    $reusable_payment_methods = $this->storage->loadReusable($this->user, $this->paymentGateway);
    $this->assertEmpty($reusable_payment_methods);
  }

  /**
   * Tests filtering reusable payment methods by billing country.
   */
  public function testBillingCountryFiltering() {
    /** @var \Drupal\profile\Entity\Profile $profile_fr */
    $profile_fr = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => [
        'organization' => '',
        'country_code' => 'FR',
        'postal_code' => '75002',
        'locality' => 'Paris',
        'address_line1' => 'A french street',
        'given_name' => 'John',
        'family_name' => 'LeSmith',
      ],
    ]);
    $profile_fr->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method_fr */
    $payment_method_fr = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'expires' => '1579132800',
      'uid' => $this->user->id(),
      'billing_profile' => $profile_fr,
    ]);
    $payment_method_fr->save();
    $payment_method_fr = $this->reloadEntity($payment_method_fr);

    $profile_us = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $profile_us->save();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method_fr */
    $payment_method_us = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'expires' => '1579132800',
      'uid' => $this->user->id(),
      'billing_profile' => $profile_us,
    ]);
    $payment_method_us->save();
    $payment_method_us = $this->reloadEntity($payment_method_us);

    $payment_methods = $this->storage->loadReusable($this->user, $this->paymentGateway, ['US']);
    $this->assertCount(1, $payment_methods);
    $this->assertEquals([$payment_method_us], array_values($payment_methods));

    $payment_methods = $this->storage->loadReusable($this->user, $this->paymentGateway, ['FR']);
    $this->assertCount(1, $payment_methods);
    $this->assertEquals([$payment_method_fr], array_values($payment_methods));

    // Disable the collection of billing information.
    $this->paymentGateway->setPluginConfiguration([
      'collect_billing_information' => FALSE,
      'api_key' => '2342fewfsfs',
      'mode' => 'test',
      'payment_method_types' => ['credit_card'],
    ]);
    // Confirm that no filtering is done.
    $payment_methods = $this->storage->loadReusable($this->user, $this->paymentGateway, ['FR']);
    $this->assertCount(2, $payment_methods);
    $this->assertEquals([$payment_method_us, $payment_method_fr], array_values($payment_methods));
  }

}
