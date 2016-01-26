<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Tests\StoreTest.
 */

namespace Drupal\commerce_store\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\commerce_store\Entity\Store;

/**
 * Create, view, edit, delete, and change store entities.
 *
 * @group commerce
 */
class StoreTest extends CommerceTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->type = $this->createEntity('commerce_store_type', [
      'id' => 'foo',
      'label' => 'Label of foo',
    ]);
  }

  /**
   * Tests creating a store programmatically and via UI.
   */
  public function testCreateStore() {
    // Create a store programmatically.
    $store = $this->createStore([
      'type' => $this->type->id(),
      'name' => $this->randomMachineName(8),
    ]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertTrue($store_exists, 'The new store has been created in the database.');

    // Create a store through the form.
    $this->drupalGet('admin/commerce/stores');
    $this->clickLink('Add a new store');
    $this->clickLink($this->type->label());

    // Check the integrity of the form.
    $this->assertFieldByName('name[0][value]', NULL, 'Name field is present.');
    $this->assertFieldByName('mail[0][value]', NULL, 'Email field is present.');
    $this->assertFieldByName('address[0][country_code]', NULL, 'Address field is present.');
    $this->assertFieldByName('billing_countries[]', NULL, 'Supported billing countries field is present');
    $this->assertFieldByName('uid[0][target_id]', NULL, 'Owner field is present');
    $this->assertFieldByName('default', NULL, 'Default field is present');
    $this->assertFieldsByValue(t('Save'), NULL, 'Save button is present');

    // Build store form values.
    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'mail[0][value]' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'EUR',
    ];

    // Add an US address for the store.
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

    // Save the store.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Test results.
    $stores_number = $this->cssSelect('.view-commerce-stores tr td.views-field-name');
    $this->assertEqual(count($stores_number), 2, 'Stores exists in the table.');
  }

  /**
   * Tests editing a store through the edit form.
   */
  public function testEditStore() {
    // Create a new store.
    $store = $this->createStore([
      'type' => $this->type->id(),
      'name' => $this->randomMachineName(8),
    ]);

    // Get the edit form page.
    $this->drupalGet('admin/commerce/stores');
    $this->clickLink(t('Edit'));

    // Only change the name.
    $new_store_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_store_name,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Test results
    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_changed = Store::load($store->id());
    $this->assertEqual($new_store_name, $store_changed->getName(), 'The store name successfully updated.');
  }

  /**
   * Tests deleting a store programmatically and from UI.
   */
  public function testDeleteStore() {
    // Create a new store.
    $store = $this->createStore([
      'type' => $this->type->id(),
      'name' => $this->randomMachineName(8),
    ]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertTrue($store_exists, 'The new store has been created in the database.');

    // Delete the Store and verify deletion.
    $store->delete();

    // Test results.
    $store_exists = (bool) Store::load($store->id());
    $this->assertFalse($store_exists, 'The new store has been deleted from the database.');

    // Delete a store from UI.
    $store = $this->createStore([
      'type' => $this->type->id(),
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($store->toUrl('delete-form'));
    $this->assertResponse(200, 'The store delete form can be accessed.');
    $this->assertText(t('This action cannot be undone.'), 'The store delete confirmation form is available.');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    // Test results.
    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertFalse($store_exists, 'The new store has been deleted from the database using UI.');
  }

  /**
   * Creates a new store entity.
   * Needed not to multiplicate code for fields as mail, currency and address.
   *
   * @param array $values
   *   An array of values.
   *   Example: 'name' => 'Foo store'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new store entity.
   */
  protected function createStore(array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $values += [
      'mail' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'USD',
      'address' => [
        'country_code' => 'US',
        'address_line1' => '1098 Alta Ave',
        'locality' => 'Mountain View',
        'administrative_area' => 'US-CA',
        'postal_code' => '94043',
      ],
    ];
    $store = $this->createEntity('commerce_store', $values);

    return $store;
  }

}
