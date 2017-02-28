<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the payment method UI.
 *
 * @group commerce
 */
class PaymentMethodTest extends CommerceBrowserTestBase {

  /**
   * A normal user with minimum permissions.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $user;

  /**
   * The payment method collection url.
   *
   * @var string
   */
  protected $collectionUrl;

  /**
   * Payment method entity storage.
   *
   * @var \Drupal\commerce_payment\PaymentMethodStorageInterface
   */
  public $paymentMethodStorage;

  /**
   * An on-site payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * {@inheritdoc}
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

    $permissions = [
      'manage own commerce_payment_method',
    ];
    $this->user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->user);

    $this->collectionUrl = 'user/' . $this->user->id() . '/payment-methods';

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $this->paymentMethodStorage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_payment_method');
  }

  /**
   * Tests accessing another user's payment method pages.
   */
  public function testDifferentUserAccess() {
    $this->drupalGet('user/' . $this->adminUser->id() . '/payment-methods');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('user/' . $this->adminUser->id() . '/payment-methods/add');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests creating a payment method.
   */
  public function testPaymentMethodCreation() {
    /** @var \Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OnsiteInterface $plugin */
    $this->drupalGet($this->collectionUrl);
    $this->getSession()->getPage()->clickLink('Add payment method');
    $this->assertSession()->addressEquals($this->collectionUrl . '/add');

    $form_values = [
      'payment_method[payment_details][number]' => '4111111111111111',
      'payment_method[payment_details][expiration][month]' => '01',
      'payment_method[payment_details][expiration][year]' => date('Y') + 1,
      'payment_method[payment_details][security_code]' => '111',
      'payment_method[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_method[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_method[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_method[billing_information][address][0][address][locality]' => 'New York City',
      'payment_method[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_method[billing_information][address][0][address][postal_code]' => '10001',
    ];
    $this->submitForm($form_values, 'Save');
    $this->assertSession()->addressEquals($this->collectionUrl);
    $this->assertSession()->pageTextContains('Visa ending in 1111 saved to your payment methods.');

    $payment_method = $this->paymentMethodStorage->load(1);
    $this->assertEquals($this->user->id(), $payment_method->getOwnerId());
  }

  /**
   * Tests deleting a payment method.
   */
  public function testPaymentMethodDeletion() {
    $payment_method = $this->createEntity('commerce_payment_method', [
      'uid' => $this->user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'example',
    ]);

    $details = [
      'type' => 'visa',
      'number' => '4111111111111111',
      'expiration' => ['month' => '01', 'year' => date("Y") + 1],
    ];
    $this->paymentGateway->getPlugin()->createPaymentMethod($payment_method, $details);
    $this->paymentGateway->save();

    $this->drupalGet($this->collectionUrl . '/' . $payment_method->id() . '/delete');

    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals($this->collectionUrl);

    $payment_method = $this->paymentMethodStorage->loadUnchanged($payment_method->id());
    $this->assertNull($payment_method);
  }

  /**
   * Tests deleting a payment method.
   */
  public function testPaymentMethodSetDefault() {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->createEntity('commerce_payment_method', [
      'uid' => $this->user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'example',
    ]);

    $details = [
      'type' => 'visa',
      'number' => '4111111111111111',
      'expiration' => ['month' => '01', 'year' => date("Y") + 1],
    ];
    $this->paymentGateway->getPlugin()->createPaymentMethod($payment_method, $details);
    $payment_method = $this->paymentMethodStorage->loadUnchanged($payment_method->id());
    // The first payment method should be set as default.
    $this->assertTrue($payment_method->isDefault());
    $this->drupalGet($this->collectionUrl);
    $this->assertSession()->pageTextNotContains('Mark as default');

    // Add a second payment method and test the 'Mark as default' operation.
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method2 */
    $payment_method2 = $this->createEntity('commerce_payment_method', [
      'uid' => $this->user->id(),
      'type' => 'credit_card',
      'payment_gateway' => 'example',
    ]);

    $details = [
      'type' => 'mastercard',
      'number' => '5555555555554444',
      'expiration' => ['month' => '01', 'year' => date("Y") + 1],
    ];
    $this->paymentGateway->getPlugin()->createPaymentMethod($payment_method2, $details);
    $this->assertFalse($payment_method2->isDefault());

    $this->drupalGet($this->collectionUrl);
    $this->getSession()->getPage()->clickLink('Mark as default');
    $this->assertSession()->addressEquals($this->collectionUrl);
    $this->assertSession()->pageTextContains(t('The @label payment method has been marked as default.', ['@label' => $payment_method2->label()]));

    // Test the updated payment methods.
    $payment_method = $this->paymentMethodStorage->loadUnchanged($payment_method->id());
    $payment_method2 = $this->paymentMethodStorage->loadUnchanged($payment_method2->id());
    $this->assertTrue($payment_method2->isDefault());
    $this->assertFalse($payment_method->isDefault());
  }

}
