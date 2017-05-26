<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the payment method UI for Manual type.
 *
 * @group commerce
 */
class ManualPaymentMethodTest extends CommerceBrowserTestBase {

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

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $this->paymentGateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'example_manual',
      'label' => 'Example Manual',
      'plugin' => 'manual',
    ]);
    $this->paymentGateway->getPlugin()->setConfiguration([
      'reusable' => '1',
      'expires' => '',
      'instructions' => [
        'value' => 'Test instructions.',
        'format' => 'plain_text',
      ],
      'payment_method_types' => ['manual'],
    ]);
    $this->paymentGateway->save();
  }

  /**
   * Tests creating a payment method.
   */
  public function testPaymentMethodCreation() {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $plugin */
    $this->drupalGet($this->collectionUrl);
    $this->getSession()->getPage()->clickLink('Add payment method');
    $this->assertSession()->addressEquals($this->collectionUrl . '/add');

    $form_values = [
      'payment_method[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_method[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_method[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_method[billing_information][address][0][address][locality]' => 'New York City',
      'payment_method[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_method[billing_information][address][0][address][postal_code]' => '10001',
    ];
    $this->submitForm($form_values, 'Save');
    $this->assertSession()->addressEquals($this->collectionUrl);
    $this->assertSession()->pageTextContains('Manual for Johnny Appleseed (123 New York Drive, New York City)');

    $payment_method = PaymentMethod::load(1);
    $this->assertEquals($this->user->id(), $payment_method->getOwnerId());
  }

  /**
   * Tests deleting a payment method.
   */
  public function testPaymentMethodDeletion() {
    $payment_method = $this->createEntity('commerce_payment_method', [
      'uid' => $this->user->id(),
      'type' => 'manual',
      'payment_gateway' => 'example_manual',
    ]);

    $details = [];
    $this->paymentGateway->getPlugin()->createPaymentMethod($payment_method, $details);
    $this->paymentGateway->save();

    $this->drupalGet($this->collectionUrl . '/' . $payment_method->id() . '/delete');

    $this->assertSession()->pageTextContains('Manual - Example Manual');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->addressEquals($this->collectionUrl);

    $payment_gateway = PaymentMethod::load($payment_method->id());
    $this->assertNull($payment_gateway);
  }

}
