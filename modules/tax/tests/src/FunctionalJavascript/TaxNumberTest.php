<?php

namespace Drupal\Tests\commerce_tax\FunctionalJavascript;

use Drupal\commerce\UrlData;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Core\Url;
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
  protected $defaultTheme = 'classy';

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
    $this->assertSession()->assertWaitOnAjaxRequest();

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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('Tax number', '601');
    $this->submitForm([], 'Save');

    $this->customerProfile = $this->reloadEntity($this->customerProfile);
    $tax_number_value = $this->customerProfile->get('tax_number')->first()->getValue();
    $this->assertEquals($original_nonce, $tax_number_value['verification_result']['nonce']);

    // Confirm that changing the tax number re-verifies the number.
    $this->drupalGet($this->order->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Country', 'ME');
    $this->assertSession()->assertWaitOnAjaxRequest();
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
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('Country', 'MK');
    $this->assertSession()->assertWaitOnAjaxRequest();
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
    $this->assertStringContainsString('Tax number', $rendered_field->getHtml());
    $this->assertStringContainsString('122', $rendered_field->getHtml());
    $this->assertFalse($rendered_field->hasLink('122'));
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertEmpty($state_field);

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '123',
      'verification_state' => VerificationResult::STATE_SUCCESS,
      'verification_timestamp' => strtotime('2019/08/08'),
      'verification_result' => ['name' => 'Centarro LLC'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertStringContainsString('Tax number', $rendered_field->getHtml());
    $this->assertTrue($rendered_field->hasLink('123'));
    $this->assertFalse($rendered_field->hasLink('Reverify'));
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Success', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--success'));

    // Confirm that the verification result can be viewed.
    $this->clickLink('123');
    $this->assertSession()->pageTextContains('August 8, 2019 - 00:00');
    $this->assertSession()->pageTextContains('Centarro LLC');

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '124',
      'verification_state' => VerificationResult::STATE_FAILURE,
      'verification_timestamp' => strtotime('2019/08/09'),
      'verification_result' => ['name' => 'Google LLC'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertStringContainsString('Tax number', $rendered_field->getHtml());
    $this->assertTrue($rendered_field->hasLink('124'));
    $this->assertFalse($rendered_field->hasLink('Reverify'));
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Failure', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--failure'));

    // Confirm that the verification result can be viewed.
    $this->clickLink('124');
    $this->assertSession()->pageTextContains('August 9, 2019 - 00:00');
    $this->assertSession()->pageTextContains('Google LLC');

    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '125',
      'verification_state' => VerificationResult::STATE_UNKNOWN,
      'verification_timestamp' => strtotime('2019/08/10'),
      'verification_result' => ['error' => 'http_429'],
    ]);
    $this->customerProfile->save();

    $this->drupalGet($this->order->toUrl('canonical'));
    $rendered_field = $this->getSession()->getPage()->find('css', '.field--name-tax-number');
    $this->assertStringContainsString('Tax number', $rendered_field->getHtml());
    $this->assertTrue($rendered_field->hasLink('125'));
    $this->assertTrue($rendered_field->hasLink('Reverify'));
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertNotEmpty($state_field);
    $this->assertEquals('Verification state: Unknown', $state_field->getAttribute('title'));
    $this->assertTrue($state_field->hasClass('commerce-tax-number__verification-icon--unknown'));

    // Confirm that the verification result can be viewed.
    $this->clickLink('125');
    $this->assertSession()->pageTextContains('August 10, 2019 - 00:00');
    $this->assertSession()->pageTextContains('Too many requests.');

    // Confirm that the number can be reverified.
    $this->drupalGet($this->order->toUrl('canonical'));
    $this->clickLink('Reverify');
    $this->assertSession()->pageTextContains('The tax number 125 has been reverified.');

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
    $this->assertStringContainsString('Tax number', $rendered_field->getHtml());
    $this->assertStringContainsString('126', $rendered_field->getHtml());
    $state_field = $rendered_field->find('css', '.commerce-tax-number__verification-icon');
    $this->assertEmpty($state_field);
  }

  /**
   * Tests access control for the verification endpoints.
   */
  public function testVerificationEndpointAccess() {
    $this->customerProfile->set('tax_number', [
      'type' => 'serbian_vat',
      'value' => '124',
      'verification_state' => VerificationResult::STATE_FAILURE,
      'verification_timestamp' => strtotime('2019/08/09'),
      'verification_result' => ['name' => 'Google LLC'],
    ]);
    $this->customerProfile->save();

    // Valid url.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile', $this->customerProfile->id(), 'tax_number', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextNotContains('Access Denied');
    $this->assertSession()->pageTextContains('Google LLC');

    // The tax_number doesn't match the one on the parent entity.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '125',
      'context' => UrlData::encode([
        'profile', $this->customerProfile->id(), 'tax_number', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Invalid context.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => 'INVALID',
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Incorrect number of parameters.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Invalid entity type.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile2', $this->customerProfile->id(), 'tax_number', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Invalid entity.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile', '99', 'tax_number', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Invalid field.
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile', $this->customerProfile->id(), 'address', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // No access to parent entity.
    $this->drupalLogout();
    $this->drupalGet(Url::fromRoute('commerce_tax.verification_result', [
      'tax_number' => '124',
      'context' => UrlData::encode([
        'profile', $this->customerProfile->id(), 'tax_number', 'default',
      ]),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');
  }

}
