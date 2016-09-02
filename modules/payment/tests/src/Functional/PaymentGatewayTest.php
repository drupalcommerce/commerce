<?php

namespace Drupal\Tests\commerce_payment\Functional;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests that commerce gateway can be properly added / edited / removed.
 *
 * @group commerce_payment
 */
class PaymentGatewayTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer payment gateways',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests a full CRUD lifecycle for a Commerce Gateway, trough the UI.
   */
  public function testGatewayCrudUi() {

    // Tests that gateways are installed from config files.
    $this->drupalGet('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Gateway Testing');

    // Create a Gateway from UI.
    $this->getSession()->getPage()->clickLink('Add payment gateway');
    $this->assertSession()
      ->addressEquals('admin/commerce/config/payment-gateways/add');

    $label = "Llama ^";
    $id = 'llama';
    $values = [
      'id' => $id,
      'label' => $label,
      'plugin' => 'gateway_testing',
    ];
    $this->submitForm($values, 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways/manage/' . $id);
    $this->assertSession()->responseContains('Saved');

    // Configure the new Gateway.
    $values += [
      'configuration[payment_method_types][dummy]' => 'dummy',
      'configuration[api_key]' => 'bunny',
    ];
    $this->submitForm($values, 'Save');

    // Check that both gateways are present.
    $this->assertSession()
      ->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseContains('Gateway Testing');
    $this->assertSession()->responseContains($label);

    // Tests gateways can be edited.
    $new_api_key = 'donkey';
    $values = [
      'configuration[payment_method_types][dummy]' => 'dummy',
      'configuration[api_key]' => $new_api_key,
    ];
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $id);
    $this->submitForm($values, 'Save');

    $gateway = PaymentGateway::load($id);
    $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    $this->assertEquals($gateway->getPlugin()->getConfiguration()['api_key'], $new_api_key, 'New API key has been updated');

    // Delete a Gateway by submitting the delete form.
    $this->drupalGet('admin/commerce/config/payment-gateways/manage/' . $id . '/delete');
    $this->submitForm([], 'Delete');

    // Check that the Gateway is deleted.
    $this->assertSession()->addressEquals('admin/commerce/config/payment-gateways');
    $this->assertSession()->responseNotContains('Gateway Testing UI');
  }

  /**
   * Tests a full CRUD lifecycle for a Commerce Gateway, trough the API.
   */
  public function testGatewayCrudProgrammatically() {

    // Create a Gateway.
    $id = 'kitten';
    $values = [
      'id' => $id,
      'label' => 'Kittens',
      'plugin' => 'gateway_testing',
    ];
    $gateway = PaymentGateway::create($values);
    $gateway->getPlugin()->setConfiguration([
      'api_key' => 'keys',
      'payment_method_types' => [
        'dummy',
      ],
    ]);
    $gateway->save();
    $gateway_exists = (bool) PaymentGateway::load($id);
    $this->assertTrue($gateway_exists, 'The gateway has been added to database.');

    $new_api_key = 'new_keys';

    // Edit a Gateway.
    $gateway = PaymentGateway::load($id);
    $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    $configs = $gateway->getPlugin()->getConfiguration();
    $this->assertEquals('keys', $configs['api_key']);
    $configs['api_key'] = $new_api_key;
    $gateway->getPlugin()->setConfiguration($configs);
    $gateway->save();

    $gateway = PaymentGateway::load($id);
    $this->assertEquals($gateway->getPlugin()->getConfiguration()['api_key'], $new_api_key, 'New API key has been updated');

    // Delete a Gateway.
    $gateway = PaymentGateway::load($id);
    $gateway->delete();

    $gateway_exists = (bool) PaymentGateway::load($id);
    $this->assertFalse($gateway_exists, 'The gateway has been removed from database.');
  }

}
