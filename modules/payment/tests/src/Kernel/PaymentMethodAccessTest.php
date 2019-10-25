<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the payment method access control.
 *
 * @coversDefaultClass \Drupal\commerce_payment\PaymentMethodAccessControlHandler
 * @group commerce
 */
class PaymentMethodAccessTest extends OrderKernelTestBase {

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
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_payment');
    $this->installConfig(['commerce_payment']);

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
    $regular_user = $this->createUser(['uid' => 2]);
    \Drupal::currentUser()->setAccount($regular_user);
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $payment_gateway->id(),
    ]);
    $payment_method->save();

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($payment_method->access('view', $account));
    $this->assertFalse($payment_method->access('update', $account));
    $this->assertFalse($payment_method->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_payment_method']);
    $this->assertTrue($payment_method->access('view', $account));
    $this->assertTrue($payment_method->access('update', $account));
    $this->assertTrue($payment_method->access('delete', $account));

    $first_account = $this->createUser([], ['manage own commerce_payment_method']);
    $second_account = $this->createUser([], ['manage own commerce_payment_method']);
    $payment_method->setOwner($first_account);
    $payment_method->save();

    $this->assertTrue($payment_method->access('view', $first_account));
    $this->assertTrue($payment_method->access('update', $first_account));
    $this->assertTrue($payment_method->access('delete', $first_account));

    $this->assertFalse($payment_method->access('view', $second_account));
    $this->assertFalse($payment_method->access('update', $second_account));
    $this->assertFalse($payment_method->access('delete', $second_account));
  }

  /**
   * @covers ::checkAccess
   */
  public function testDeletedGateway() {
    $payment_gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $payment_gateway->id(),
    ]);
    $payment_method->save();

    $payment_gateway->delete();
    $payment_method = $this->reloadEntity($payment_method);
    // Confirm that not even the administrator can update the payment
    // method if its gateway has been deleted.
    $account = $this->createUser([], ['administer commerce_payment_method']);
    $this->assertTrue($payment_method->access('view', $account));
    $this->assertFalse($payment_method->access('update', $account));
    $this->assertTrue($payment_method->access('delete', $account));
  }

  /**
   * @covers ::checkAccess
   */
  public function testUnsupportedUpdate() {
    // Commerce doesn't ship with an on-site gateway which doesn't support
    // updating payment methods, so we simulate it here with an off-site one.
    $payment_gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $payment_gateway->id(),
    ]);
    $payment_method->save();

    // Confirm that not even the administrator can update the payment
    // method if its gateway does not support it.
    $account = $this->createUser([], ['administer commerce_payment_method']);
    $this->assertTrue($payment_method->access('view', $account));
    $this->assertFalse($payment_method->access('update', $account));
    $this->assertTrue($payment_method->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_payment_method');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('credit_card', $account));

    $account = $this->createUser([], ['administer commerce_payment_method']);
    $this->assertTrue($access_control_handler->createAccess('credit_card', $account));

    $account = $this->createUser([], ['manage own commerce_payment_method']);
    $this->assertTrue($access_control_handler->createAccess('credit_card', $account));
  }

}
