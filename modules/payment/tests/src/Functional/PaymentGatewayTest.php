<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the payment gateway UI.
 *
 * @group commerce
 */
class PaymentGatewayTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_payment_gateway',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a payment gateway.
   */
  public function testPaymentGatewayCreation() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');

    $values = [
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/manage/example');
    $this->assertSession()->responseContains('Saved');

    $values += [
      'configuration[api_key]' => 'bunny',
      'configuration[mode]' => 'test',
      'status' => '1',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Example');
    $this->assertSession()->responseContains('Test');

    $payment_gateway = PaymentGateway::load('example');
    $this->assertEquals('example', $payment_gateway->id());
    $this->assertEquals('Example', $payment_gateway->label());
    $this->assertEquals('example_onsite', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('bunny', $configuration['api_key']);
  }

  /**
   * Tests editing a payment gateway.
   */
  public function testPaymentGatewayEditing() {
    $values = [
      'id' => 'edit_example',
      'label' => 'Edit example',
      'plugin' => 'example_onsite',
      'status' => TRUE,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id());
    $values += [
      'configuration[api_key]' => 'donkey',
      'configuration[mode]' => 'live',
    ];
    $this->submitForm($values, 'Save');

    \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway')->resetCache();
    $payment_gateway = PaymentGateway::load('edit_example');
    $this->assertEquals('edit_example', $payment_gateway->id());
    $this->assertEquals('Edit example', $payment_gateway->label());
    $this->assertEquals('example_onsite', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('live', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('donkey', $configuration['api_key']);
  }

  /**
   * Tests deleting a payment gateway.
   */
  public function testPaymentGatewayDeletion() {
    $payment_gateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'for_deletion',
      'label' => 'For deletion',
      'plugin' => 'example_onsite',
    ]);
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');

    $payment_gateway_exists = (bool) PaymentGateway::load('for_deletion');
    $this->assertFalse($payment_gateway_exists, 'The payment gateway has been deleted from the database.');
  }

}
