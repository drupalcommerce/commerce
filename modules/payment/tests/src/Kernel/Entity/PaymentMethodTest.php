<?php

namespace Drupal\Tests\commerce_payment\Kernel\Entity;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\CreditCard;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the payment method entity.
 *
 * @coversDefaultClass \Drupal\commerce_payment\Entity\PaymentMethod
 *
 * @group commerce
 */
class PaymentMethodTest extends CommerceKernelTestBase {

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

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
    $this->assertEquals(NULL, $payment_method->getOwner());
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

}
