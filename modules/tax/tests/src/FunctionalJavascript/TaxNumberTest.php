<?php

namespace Drupal\Tests\commerce_tax\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\field\Entity\FieldConfig;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the tax number widget and formatter.
 *
 * @group commerce
 */
class TaxNumberTest extends CommerceWebDriverTestBase {

  /**
   * A test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * A test profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $customerProfile;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_tax',
    'commerce_tax_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer profile',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->store->set('billing_countries', ['RS', 'ME', 'MK']);
    $this->store->save();

    // The tax number field is not exposed by default.
    $form_display = commerce_get_entity_display('profile', 'customer', 'form');
    $form_display->setComponent('tax_number', [
      'type' => 'commerce_tax_number_default',
    ]);
    $form_display->save();

    // Limit the available countries.
    $field = FieldConfig::loadByName('profile', 'customer', 'tax_number');
    $field->setSetting('countries', ['RS', 'ME']);
    $field->save();

    $this->customerProfile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => [
        'country_code' => 'RS',
        'postal_code' => '11000',
        'locality' => 'Belgrade',
        'address_line1' => 'Cetinjska 15',
        'given_name' => 'Dusan',
        'family_name' => 'Popov',
      ],
    ]);
    $this->customerProfile->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();

    $this->order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->adminUser,
      'billing_profile' => $this->customerProfile,
      'order_items' => [$order_item],
      'state' => 'completed',
    ]);
    $this->order->save();
  }

  /**
   * Tests the widget.
   */
  public function testWidget() {
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->waitForAjaxToFinish();

    // Confirm that the field is present for the allowed country (RS).
    $this->assertSession()->fieldExists('Tax number');
    $this->getSession()->getPage()->fillField('Tax number', '601');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $tax_number_value = $this->customerProfile->get('tax_number')->first()->getValue();
    $this->assertEquals('serbian_vat', $tax_number_value['type']);
    $this->assertEquals('601', $tax_number_value['value']);
    $this->assertEquals('success', $tax_number_value['verification_state']);
    $this->assertArrayHasKey('nonce', $tax_number_value['verification_result']);
    $original_nonce = $tax_number_value['verification_result']['nonce'];

    // Confirm that not changing the tax number does not re-verify the number.
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldValueEquals('Tax number', '601');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $tax_number_value = $this->customerProfile->get('tax_number')->first()->getValue();
    $this->assertEquals($original_nonce, $tax_number_value['verification_result']['nonce']);

    // Confirm that changing the tax number re-verifies the number.
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldValueEquals('Tax number', '601');
    $this->getSession()->getPage()->fillField('Tax number', '603');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $tax_number_value = $this->customerProfile->get('tax_number')->first()->getValue();
    $this->assertEquals('serbian_vat', $tax_number_value['type']);
    $this->assertEquals('603', $tax_number_value['value']);
    $this->assertEquals('success', $tax_number_value['verification_state']);
    $this->assertArrayHasKey('nonce', $tax_number_value['verification_result']);
    $this->assertNotEquals($original_nonce, $tax_number_value['verification_result']);

    // Confirm that changing the country changes the tax number type.
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->selectFieldOption('Country', 'ME');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('City', 'Podgorica');
    $this->assertSession()->fieldValueEquals('Tax number', '603');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $tax_number_value = $this->customerProfile->get('tax_number')->first()->getValue();
    $this->assertEquals('other', $tax_number_value['type']);
    $this->assertEquals('603', $tax_number_value['value']);
    $this->assertNull($tax_number_value['verification_state']);
    $this->assertNull($tax_number_value['verification_timestamp']);
    $this->assertEmpty($tax_number_value['verification_result']);

    // Confirm that selecting a non-allowed country removes the field.
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->selectFieldOption('Country', 'MK');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('City', 'Skopje');
    $this->assertSession()->fieldNotExists('Tax number');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $this->assertTrue($this->customerProfile->get('tax_number')->isEmpty());
  }

  /**
   * Tests the formatter.
   */
  public function testFormatter() {
    $this->customerProfile->set('tax_number', [
      'type' => 'other',
      'value' => '122',
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertContains('Tax number', $rendered_field->getHtml());
    $this->assertContains('122', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertEmpty($state_field);

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '123',
      'verification_state' => VerificationResult::STATE_SUCCESS,
      'verification_timestamp' => strtotime('2019/08/08'),
      'verification_result' => ['verification_id' => '123456'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertContains('Tax number', $rendered_field->getHtml());
    $this->assertContains('123', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Success', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--success'));

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '124',
      'verification_state' => VerificationResult::STATE_FAILURE,
      'verification_timestamp' => strtotime('2019/08/09'),
      'verification_result' => ['verification_id' => '123457'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertContains('Tax number', $rendered_field->getHtml());
    $this->assertContains('124', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Failure', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--failure'));

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '125',
      'verification_state' => VerificationResult::STATE_UNKNOWN,
      'verification_timestamp' => strtotime('2019/08/10'),
      'verification_result' => ['verification_id' => '123458'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertContains('Tax number', $rendered_field->getHtml());
    $this->assertContains('125', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Unknown', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--unknown'));

    // Confirm that invalid verification states are ignored.
    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '126',
      'verification_state' => 'INVALID',
      'verification_timestamp' => strtotime('2019/08/10'),
      'verification_result' => ['verification_id' => '123458'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertContains('Tax number', $rendered_field->getHtml());
    $this->assertContains('126', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertEmpty($state_field);
  }

}
