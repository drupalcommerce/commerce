<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Tests\StoreTypeTest.
 */

namespace Drupal\commerce_store\Tests;

use Drupal\commerce_store\Entity\StoreType;

/**
 * Ensure the store type works correctly.
 *
 * @group commerce
 */
class StoreTypeTest extends StoreTestBase {

  /**
   * Tests if the default Store Type was created.
   */
  public function testDefaultStoreType() {
    $this->drupalGet('admin/commerce/config/store-types');
    $store_types = StoreType::loadMultiple();

    $this->assertTrue(isset($store_types['default']), 'The default store type is available');

    $store_type = StoreType::load('default');
    $this->assertEqual($store_types['default'], $store_type, 'The correct store type is loaded');
  }

  /**
   * Tests if the correct number of Store Types are being listed.
   */
  public function testListStoreType() {
    $title = strtolower($this->randomMachineName(8));
    $table_selector = 'table tbody tr';

    // The store shows one default store type.
    $this->drupalGet('admin/commerce/config/store-types');
    $store_types = $this->cssSelect($table_selector);
    $this->assertEqual(count($store_types), 1, 'Stores types are correctly listed');

    // Create a new commerce store type entity and see if the list has two store types.
    $this->createEntity('commerce_store_type', [
        'id' => $title,
        'label' => $title,
      ]
    );

    $this->drupalGet('admin/commerce/config/store-types');
    $store_types = $this->cssSelect($table_selector);
    $this->assertEqual(count($store_types), 2, 'Stores types are correctly listed');
  }

  /**
   * Tests creating a Store Type programaticaly and through the create form.
   */
  public function testCreateStoreType() {
    $title = strtolower($this->randomMachineName(8));

    // Create a store type programmaticaly.
    $type = $this->createEntity('commerce_store_type', [
        'id' => $title,
        'label' => $title,
      ]
    );

    $type_exists = (bool) StoreType::load($type->id());
    $this->assertTrue($type_exists, 'The new store type has been created in the database.');

    // Create a store type through the form.
    $edit = [
      'id' => 'foo',
      'label' => 'Label of foo',
    ];
    $this->drupalPostForm('admin/commerce/config/store-types/add', $edit, t('Save'));
    $type_exists = (bool) StoreType::load($edit['id']);
    $this->assertTrue($type_exists, 'The new store type has been created in the database.');
  }

  /**
   * Tests updating a Store Type through the edit form.
   */
  public function testUpdateStoreType() {
    // Create a new store type.
    $store_type = $this->createEntity('commerce_store_type', [
        'id' => 'foo',
        'label' => 'Label for foo',
      ]
    );

    // Only change the label.
    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm('admin/commerce/config/store-types/default/edit', $edit, 'Save');
    $changed = StoreType::load($store_type->id());
    $this->assertEqual($store_type->label(), $changed->label(), 'The label of the store type has been changed.');
  }

  /**
   * Tests deleting a Store Type through the form.
   */
  public function testDeleteStoreType() {
    // Create a store type programmaticaly.
    $type = $this->createEntity('commerce_store_type', [
        'id' => 'foo',
        'label' => 'Label for foo',
      ]
    );

    // Create a store.
    $store = $this->createEntity('commerce_store', [
      'type' => $type->id(),
      'name' => $this->randomMachineName(8),
      'email' => \Drupal::currentUser()->getEmail(),
    ]);

    // Try to delete the store type.
    $this->drupalGet('admin/commerce/config/store-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 store on your site. You can not remove this store type until you have removed all of the %type stores.', ['%type' => $type->label()]),
      'The store type will not be deleted until all stores of that type are deleted'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The store type deletion confirmation form is not available');

    // Deleting the store type when its not being referenced by a store.
    $store->delete();
    $this->drupalGet('admin/commerce/config/store-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the store type %type?', ['%type' => $type->label()]),
      'The store type is available for deletion'
    );
    $this->assertText(t('This action cannot be undone.'), 'The store type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $type_exists = (bool) StoreType::load($type->id());
    $this->assertFalse($type_exists, 'The new store type has been deleted from the database.');

  }
}
