<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the payment gateway UI for Manual type.
 *
 * @group commerce
 */
class ManualPaymentGatewayTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment',
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
      'plugin' => 'manual',
      'configuration[mode]' => 'test',
      'configuration[manual][reusable]' => '1',
      'configuration[manual][expires]' => '',
      'configuration[manual][instructions][value]' => 'Test instructions.',
      'status' => '1',
      'id' => 'example',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Example');
    $this->assertSession()->responseContains('Test');

    $payment_gateway = PaymentGateway::load('example');
    $this->assertEquals('example', $payment_gateway->id());
    $this->assertEquals('Example', $payment_gateway->label());
    $this->assertEquals('manual', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('test', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('1', $configuration['reusable']);
    $this->assertEquals('Test instructions.', $configuration['instructions']['value']);
    $this->assertEquals('plain_text', $configuration['instructions']['format']);
    $this->assertEmpty($configuration['expires']);
  }

  /**
   * Tests editing a payment gateway.
   */
  public function testPaymentGatewayEditing() {
    $values = [
      'id' => 'edit_example',
      'label' => 'Edit example',
      'plugin' => 'manual',
      'status' => TRUE,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id());
    $values += [
      'configuration[mode]' => 'live',
      'configuration[manual][reusable]' => '1',
      'configuration[manual][expires]' => '',
      'configuration[manual][instructions][value]' => 'Test instructions.',
    ];
    $this->submitForm($values, 'Save');

    \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway')->resetCache();
    $payment_gateway = PaymentGateway::load('edit_example');
    $this->assertEquals('edit_example', $payment_gateway->id());
    $this->assertEquals('Edit example', $payment_gateway->label());
    $this->assertEquals('manual', $payment_gateway->getPluginId());
    $this->assertEquals(TRUE, $payment_gateway->status());
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $this->assertEquals('live', $payment_gateway_plugin->getMode());
    $configuration = $payment_gateway_plugin->getConfiguration();
    $this->assertEquals('1', $configuration['reusable']);
    $this->assertEquals('Test instructions.', $configuration['instructions']['value']);
    $this->assertEquals('plain_text', $configuration['instructions']['format']);
    $this->assertEmpty($configuration['expires']);
  }

  /**
   * Tests deleting a payment gateway.
   */
  public function testPaymentGatewayDeletion() {
    $payment_gateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'for_deletion',
      'label' => 'For deletion',
      'plugin' => 'manual',
    ]);
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $payment_gateway->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');

    $payment_gateway_exists = (bool) PaymentGateway::load('for_deletion');
    $this->assertEmpty($payment_gateway_exists, 'The payment gateway has been deleted from the database.');
  }

}
