<?php

namespace Drupal\commerce_store\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\StoreCreationTrait;

/**
 * Create, view, edit, delete, and change store entities.
 *
 * @group commerce
 */
class StoreTest extends CommerceTestBase {

  use StoreCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['commerce_store'];

  /**
   * A store type entity to use in the tests.
   *
   * @var \Drupal\commerce_store\Entity\StoreTypeInterface
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer store types',
      'administer stores',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a store.
   */
  public function testCreateStore() {
    $this->drupalGet('admin/commerce/stores');
    $this->clickLink('Add a new store');

    // Check the integrity of the form.
    $this->assertFieldByName('name[0][value]', NULL, 'Name field is present.');
    $this->assertFieldByName('mail[0][value]', NULL, 'Email field is present.');
    $this->assertFieldByName('address[0][country_code]', NULL, 'Address field is present.');
    $this->assertFieldByName('billing_countries[]', NULL, 'Supported billing countries field is present');
    $this->assertFieldByName('uid[0][target_id]', NULL, 'Owner field is present');
    $this->assertFieldByName('default', NULL, 'Default field is present');
    $this->assertFieldsByValue(t('Save'), NULL, 'Save button is present');

    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'mail[0][value]' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'USD',
    ];
    $address_country = [
      'address[0][country_code]' => 'US',
    ];
    $this->drupalPostAjaxForm(NULL, $address_country, 'address[0][country_code]');
    $address = [
      'country_code' => 'US',
      'address_line1' => '1098 Alta Ave',
      'locality' => 'Mountain View',
      'administrative_area' => 'US-CA',
      'postal_code' => '94043',
    ];
    foreach ($address as $property => $value) {
      $path = 'address[0][' . $property . ']';
      $edit[$path] = $value;
    }
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $store_count = $this->cssSelect('.view-commerce-stores tr td.views-field-name');
    $this->assertEqual(count($store_count), 1, 'Stores exists in the table.');
  }

  /**
   * Tests editing a store.
   */
  public function testEditStore() {
    $store = $this->createStore();

    $this->drupalGet($store->toUrl('edit-form'));
    $new_store_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_store_name,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_changed = Store::load($store->id());
    $this->assertEqual($new_store_name, $store_changed->getName(), 'The store name successfully updated.');
  }

  /**
   * Tests deleting a store.
   */
  public function testDeleteStore() {
    $store = $this->createStore();
    $this->drupalGet($store->toUrl('delete-form'));
    $this->assertResponse(200, 'The store delete form can be accessed.');
    $this->assertText(t('This action cannot be undone.'), 'The store delete confirmation form is available.');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertFalse($store_exists, 'The new store has been deleted from the database using UI.');
  }

}
