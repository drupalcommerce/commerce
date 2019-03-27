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
   * Tests adding a payment gateway.
   */
  public function testAdd() {
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/add');

    $edit = [
      'label' => 'Example',
      'plugin' => 'example_offsite_redirect',
      'configuration[example_offsite_redirect][redirect_method]' => 'post',
      'configuration[example_offsite_redirect][mode]' => 'test',
      'status' => '1',
      // Setting the 'id' can fail if focus switches to another field.
      // This is a bug in the machine name JS that can be reproduced manually.
      'id' => 'example',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Example payment gateway.');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');

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
  public function testEdit() {
    $values = [
      'id' => 'edit_example',
      'label' => 'Edit example',
      'plugin' => 'example_offsite_redirect',
      'status' => 0,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet($payment_gateway->toUrl('edit-form'));
    $edit = $values + [
      'configuration[example_offsite_redirect][redirect_method]' => 'get',
      'configuration[example_offsite_redirect][mode]' => 'live',
      'conditionOperator' => 'OR',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Edit example payment gateway.');

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
   * Tests duplicating a payment gateway.
   */
  public function testDuplicate() {
    $values = [
      'id' => 'foo',
      'label' => 'Foo',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'get',
        'mode' => 'live',
      ],
      'status' => 0,
    ];
    $payment_gateway = $this->createEntity('commerce_payment_gateway', $values);

    $this->drupalGet($payment_gateway->toUrl('duplicate-form'));
    $this->assertSession()->fieldValueEquals('label', 'Foo');
    $this->assertSession()->fieldValueEquals('plugin', 'example_offsite_redirect');
    $this->assertSession()->fieldValueEquals('configuration[example_offsite_redirect][redirect_method]', 'get');
    $this->assertSession()->fieldValueEquals('configuration[example_offsite_redirect][mode]', 'live');

    $edit = [
      'id' => 'foo2',
      'label' => 'Foo2',
      'status' => 1,
      'configuration[example_offsite_redirect][mode]' => 'test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Foo2 payment gateway.');

    // Confirm that the original payment gateway is unchanged.
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->reloadEntity($payment_gateway);
    $this->assertNotEmpty($payment_gateway);
    $this->assertEquals('Foo', $payment_gateway->label());
    $this->assertEquals('live', $payment_gateway->getPlugin()->getMode());
    $this->assertFalse($payment_gateway->status());

    // Confirm that the new payment gateway has the expected data.
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::load('foo2');
    $this->assertNotEmpty($payment_gateway);
    $this->assertEquals('Foo2', $payment_gateway->label());
    $this->assertEquals('test', $payment_gateway->getPlugin()->getMode());
    $this->assertTrue($payment_gateway->status());
  }

  /**
   * Tests deleting a payment gateway.
   */
  public function testDelete() {
    $payment_gateway = $this->createEntity('commerce_payment_gateway', [
      'id' => 'for_deletion',
      'label' => 'For deletion',
      'plugin' => 'example_offsite_redirect',
    ]);
    $this->drupalGet($payment_gateway->toUrl('delete-form'));
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');

    $payment_gateway_exists = (bool) PaymentGateway::load('for_deletion');
    $this->assertEmpty($payment_gateway_exists, 'The payment gateway has been deleted from the database.');
  }

}
