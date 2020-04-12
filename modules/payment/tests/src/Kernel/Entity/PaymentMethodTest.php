<?php

namespace Drupal\Tests\commerce_payment\Kernel\Entity;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\CreditCard;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the payment method entity.
 *
 * @coversDefaultClass \Drupal\commerce_payment\Entity\PaymentMethod
 *
 * @group commerce
 */
class PaymentMethodTest extends OrderKernelTestBase {

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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

    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_payment');

    PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ])->save();

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * @covers ::getType
   * @covers ::getPaymentGatewayId
   * @covers ::getPaymentGatewayMode
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   * @covers ::getRemoteId
   * @covers ::setRemoteId
   * @covers ::getBillingProfile
   * @covers ::setBillingProfile
   * @covers ::isReusable
   * @covers ::setReusable
   * @covers ::isDefault
   * @covers ::setDefault
   * @covers ::isExpired
   * @covers ::getExpiresTime
   * @covers ::setExpiresTime
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   */
  public function testPaymentMethod() {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
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
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
    ]);
    $payment_method->save();

    $this->assertInstanceOf(CreditCard::class, $payment_method->getType());
    $this->assertEquals('example', $payment_method->getPaymentGatewayId());
    $this->assertEquals('test', $payment_method->getPaymentGatewayMode());

    $payment_method->setOwner($this->user);
    $this->assertEquals($this->user, $payment_method->getOwner());
    $this->assertEquals($this->user->id(), $payment_method->getOwnerId());
    $payment_method->setOwnerId(0);
    $this->assertInstanceOf(UserInterface::class, $payment_method->getOwner());
    $this->assertTrue($payment_method->getOwner()->isAnonymous());
    // Non-existent/deleted user ID.
    $payment_method->setOwnerId(890);
    $this->assertInstanceOf(UserInterface::class, $payment_method->getOwner());
    $this->assertTrue($payment_method->getOwner()->isAnonymous());
    $this->assertEquals(890, $payment_method->getOwnerId());
    $payment_method->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $payment_method->getOwner());
    $this->assertEquals($this->user->id(), $payment_method->getOwnerId());

    $payment_method->setRemoteId('123456');
    $this->assertEquals('123456', $payment_method->getRemoteId());

    $payment_method->setBillingProfile($profile);
    $this->assertEquals($profile, $payment_method->getBillingProfile());

    $this->assertNotEmpty($payment_method->isReusable());
    $payment_method->setReusable(FALSE);
    $this->assertEmpty($payment_method->isReusable());

    $this->assertFalse($payment_method->isDefault());
    $payment_method->setDefault(TRUE);
    $this->assertTrue($payment_method->isDefault());

    $this->assertFalse($payment_method->isExpired());
    $payment_method->setExpiresTime(635879700);
    $this->assertTrue($payment_method->isExpired());
    $this->assertEquals(635879700, $payment_method->getExpiresTime());

    $payment_method->setCreatedTime(635879700);
    $this->assertEquals(635879700, $payment_method->getCreatedTime());
  }

  /**
   * @covers ::preSave
   */
  public function testPreSave() {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
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
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => 'example',
      'billing_profile' => $profile,
    ]);
    $payment_method->save();

    // Confirm that the payment_gateway_mode field is populated.
    $this->assertEquals('test', $payment_method->getPaymentGatewayMode());

    // Confirm that saving the payment method reassigns the billing profile.
    $payment_method->save();
    $this->assertEquals(0, $payment_method->getBillingProfile()->getOwnerId());
    $this->assertEquals($profile->id(), $payment_method->getBillingProfile()->id());
  }

}
