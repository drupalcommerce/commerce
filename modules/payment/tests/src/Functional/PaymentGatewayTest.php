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
      'label' => 'Example',
      'plugin' => 'example_offsite_redirect',
      'configuration[example_offsite_redirect][redirect_method]' => 'post',
      'configuration[example_offsite_redirect][mode]' => 'test',
      'status' => '1',
      // Setting the 'id' can fail if focus switches to another field.
      // This is a bug in the machine name JS that can be reproduced manually.
      'id' => 'example',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Example');
    $this->assertSession()->responseContains('Test');

    $payment_gateway = PaymentGateway::load('example');
    $this->assertEquals('example', $payment_gateway->id());
    $this->assertEquals('Example', $payment_gateway->label());
    $this->assertEquals('example_offsite_redirect', $payment_gateway->getPluginId());
    $this->assertEmpty($payment_gateway->getConditions());
    $this->assertEquals('AND', $payment_gateway->getConditionOperator());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('post', $configuration['redirect_method']);
  }

  /**
   * Tests editing a payment gateway.
   */
  public function testPaymentGatewayEditing() {
    $values = [
      'id' => 'edit_example',
      'label' => 'Edit example',
      'plugin' => 'example_offsite_redirect',
      'status' => 0,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id());
    $values += [
      'configuration[example_offsite_redirect][redirect_method]' => 'get',
      'configuration[example_offsite_redirect][mode]' => 'live',
      'conditionOperator' => 'OR',
    ];
    $this->submitForm($values, 'Save');

    \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway')->resetCache();
    $payment_gateway = PaymentGateway::load('edit_example');
    $this->assertEquals('edit_example', $payment_gateway->id());
    $this->assertEquals('Edit example', $payment_gateway->label());
    $this->assertEquals('example_offsite_redirect', $payment_gateway->getPluginId());
    $this->assertEmpty($payment_gateway->getConditions());
    $this->assertEquals('OR', $payment_gateway->getConditionOperator());
    $this->assertEquals(FALSE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('live', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('get', $configuration['redirect_method']);
  }

  /**
   * Tests deleting a payment gateway.
   */
  public function testPaymentGatewayDeletion() {
    $payment_gateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'for_deletion',
      'label' => 'For deletion',
      'plugin' => 'example_offsite_redirect',
    ]);
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');

    $payment_gateway_exists = (bool) PaymentGateway::load('for_deletion');
    $this->assertEmpty($payment_gateway_exists, 'The payment gateway has been deleted from the database.');
  }

}
