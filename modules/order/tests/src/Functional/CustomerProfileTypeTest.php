<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Tests the management of customer profile types.
 *
 * @group commerce
 */
class CustomerProfileTypeTest extends OrderBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer profile types',
      'administer profile fields',
      'administer profile',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Work around profile bug #3071142. Remove once 1.0 is required.
    $user_form_display = EntityFormDisplay::load('user.user.default');
    if (!$user_form_display) {
      $user_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'user',
        'bundle' => 'user',
        'mode' => 'default',
        'status' => TRUE,
      ]);
      $user_form_display->save();
    }
  }

  /**
   * Tests the profile type UI.
   */
  public function testProfileTypeUi() {
    $profile_type = ProfileType::load('customer');
    // Confirm that the "customer" profile type is not deletable.
    $this->assertFalse($profile_type->access('delete'));

    // Confirm that the "customer profile type" flag is set on the "customer"
    // profile type, and that it can't be disabled from the UI.
    $this->assertTrue($profile_type->getThirdPartySetting('commerce_order', 'customer_profile_type'));
    $this->drupalGet($profile_type->toUrl('edit-form'));
    $checkbox = $this->getSession()->getPage()->findField('commerce_order[customer_profile_type]');
    $this->assertNotEmpty($checkbox);
    $this->assertNotEmpty($checkbox->getAttribute('checked'));
    $this->assertNotEmpty($checkbox->getAttribute('disabled'));
    $this->submitForm([], 'Save');
    // Confirm that saving the form doesn't unset the flag.
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $this->reloadEntity($profile_type);
    $this->assertTrue($profile_type->getThirdPartySetting('commerce_order', 'customer_profile_type'));

    // Confirm that the flag is unset by default when adding a new profile type.
    $this->drupalGet('admin/config/people/profile-types/add');
    $checkbox = $this->getSession()->getPage()->findField('commerce_order[customer_profile_type]');
    $this->assertNotEmpty($checkbox);
    $this->assertEmpty($checkbox->getAttribute('checked'));
    $this->assertEmpty($checkbox->getAttribute('disabled'));

    $this->submitForm([
      'label' => 'Customer (Shipping information)',
      'display_label' => 'Shipping information',
      'id' => 'customer_shipping',
    ], 'Save');
    $profile_type = ProfileType::load('customer_shipping');
    $this->assertFalse($profile_type->getThirdPartySetting('commerce_order', 'customer_profile_type'));
    // Confirm that an address field was not attached.
    $address_field = FieldConfig::loadByName('profile', 'customer_shipping', 'address');
    $this->assertEmpty($address_field);

    // Confirm that the flag can be set.
    $this->drupalGet($profile_type->toUrl('edit-form'));
    $this->submitForm([
      'commerce_order[customer_profile_type]' => TRUE,
    ], 'Save');
    $profile_type = $this->reloadEntity($profile_type);
    $this->assertTrue($profile_type->getThirdPartySetting('commerce_order', 'customer_profile_type'));
    // Confirm that an address field was attached.
    $address_field = FieldConfig::loadByName('profile', 'customer_shipping', 'address');
    $this->assertNotEmpty($address_field);
    $this->assertEquals('Address', $address_field->getLabel());
    $this->assertTrue($address_field->isRequired());

    // Confirm that the edit form reflects the updated flag.
    $this->drupalGet($profile_type->toUrl('edit-form'));
    $checkbox = $this->getSession()->getPage()->findField('commerce_order[customer_profile_type]');
    $this->assertNotEmpty($checkbox);
    $this->assertNotEmpty($checkbox->getAttribute('checked'));
    $this->assertEmpty($checkbox->getAttribute('disabled'));

    // Confirm that the flag can't be unset once there's data.
    $profile = Profile::create([
      'type' => 'customer_shipping',
      'address' => [
        'country_code' => 'US',
        'administrative_area' => 'SC',
        'locality' => 'Greenville',
        'postal_code' => '29616',
        'address_line1' => '9 Drupal Ave',
        'given_name' => 'Bryan',
        'family_name' => 'Centarro',
      ],
    ]);
    $profile->save();
    $this->drupalGet($profile_type->toUrl('edit-form'));
    $checkbox = $this->getSession()->getPage()->findField('commerce_order[customer_profile_type]');
    $this->assertNotEmpty($checkbox);
    $this->assertNotEmpty($checkbox->getAttribute('checked'));
    $this->assertNotEmpty($checkbox->getAttribute('disabled'));

    // Confirm that unsetting the flag removes the address field.
    $profile->delete();
    $this->drupalGet($profile_type->toUrl('edit-form'));
    $this->submitForm([
      'commerce_order[customer_profile_type]' => FALSE,
    ], 'Save');
    $address_field = FieldConfig::loadByName('profile', 'customer_shipping', 'address');
    $this->assertEmpty($address_field);
  }

  /**
   * Tests the address field UI.
   */
  public function testAddressFieldUi() {
    $profile_type = ProfileType::load('customer');
    // Create a new profile type to confirm that non-default customer profile
    // types have the same behavior.
    $this->drupalGet($profile_type->toUrl('duplicate-form'));
    $this->submitForm([
      'label' => 'Customer (Shipping information)',
      'display_label' => 'Shipping information',
      'id' => 'customer_shipping',
    ], 'Save');
    $new_profile_type = ProfileType::load('customer_shipping');
    $this->assertNotEmpty($new_profile_type);

    $fields_ui_url = Url::fromRoute('entity.profile.field_ui_fields', [
      'profile_type' => 'customer_shipping',
    ]);
    $this->drupalGet($fields_ui_url);
    $operation_links = $this->xpath('//tr[@id="address"]//ul[@class = "dropbutton"]/li/a');
    $link_labels = [];
    foreach ($operation_links as $link) {
      $link_labels[] = $link->getText();
    }
    // Confirm that the field cannot be deleted.
    $this->assertNotContains('Delete', $link_labels);
    // Confirm that the "Storage settings" page is not available.
    $this->assertNotContains('Storage settings', $link_labels);

    $field_config = FieldConfig::loadByName('profile', 'customer_shipping', 'address');
    $this->drupalGet($field_config->toUrl('profile-field-edit-form'));
    // Confirm that the "Required" and "Available countries" field settings
    // are not available.
    $this->assertSession()->fieldNotExists('required');
    $this->assertSession()->fieldNotExists('settings[available_countries][]');
  }

}
