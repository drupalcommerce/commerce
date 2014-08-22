<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\CommerceStoreCRUDTest
 */

namespace Drupal\commerce\Tests;

use Drupal\commerce\Entity\CommerceStore;
use Drupal\commerce\Entity\CommerceStoreType;

/**
 * Create, view, edit, delete, and change store entities.
 *
 * @group commerce
 */
class CommerceStoreCRUDTest extends CommerceTestBase {

  /** @var CommerceStoreType */
  protected $type;

  protected function setUp() {
    parent::setUp();

    $this->type = $this->createEntity('commerce_store_type', array(
        'id' => 'foo',
        'label' => 'Label of foo'
      )
    );
  }

  /**
   * Tests creating a store programaticaly and through the create form.
   */
  public function testCreateStore() {
    $name = strtolower($this->randomMachineName(8));
    // Create a store programmaticaly.
    $store = $this->createEntity('commerce_store', array(
        'type' => $this->type->id(),
        'name' => $name,
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR'
      )
    );
    $store_exist = (bool) CommerceStore::load($store->id());
    $this->assertTrue($store_exist, 'The new store has been created in the database.');

    // Create a store through the form.
    $this->drupalGet('admin/commerce/config/store');
    $this->clickLink('Add a new store');
    $this->clickLink($this->type->label());
    $edit = array(
      'name' => 'Foo Store',
      'mail' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'EUR'
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Tests updating a store through the edit form.
   */
  public function testUpdateStore() {
    // Create a new store.
    $store = $this->createEntity('commerce_store', array(
        'type' => $this->type->id(),
        'name' => $this->randomMachineName(8),
        'email' => \Drupal::currentUser()->getEmail()
      )
    );

    $this->drupalGet('admin/commerce/config/store');
    $this->clickLink(t('Edit'));
    // Only change the name.
    $edit = array(
      'name' => $this->randomMachineName(8),
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $store_changed = CommerceStore::load($store->id());
    $this->assertEqual($store->getName(), $store_changed->getName(), 'The name of the store has been changed.');
  }

  /**
   * Tests deleting a store.
   */
  public function testDeleteStore() {
    // Create a new store.
    $store = $this->createEntity('commerce_store', array(
        'type' => $this->type->id(),
        'name' => $this->randomMachineName(8),
        'email' => \Drupal::currentUser()->getEmail()
      )
    );
    $store_exist = (bool) CommerceStore::load($store->id());
    $this->assertTrue($store_exist, 'The new store has been created in the database.');

    // Delete the Store and verify deletion.
    $store->delete();
    $store_exist = (bool) CommerceStore::load($store->id());
    $this->assertFalse($store_exist, 'The new store has been deleted from the database.');
  }
}
