<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Tests\StoreTest.
 */

namespace Drupal\commerce_store\Tests;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreType;

/**
 * Create, view, edit, delete, and change store entities.
 *
 * @group commerce
 */
class StoreTest extends StoreTestBase {

  /**
   * A store type entity to use in the tests.
   *
   * @var StoreType
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->type = $this->createEntity('commerce_store_type', [
        'id' => 'foo',
        'label' => 'Label of foo',
      ]
    );
  }

  /**
   * Tests creating a store programaticaly and through the create form.
   */
  public function testCreateStore() {
    $name = strtolower($this->randomMachineName(8));
    // Create a store programmaticaly.
    $store = $this->createEntity('commerce_store', [
        'type' => $this->type->id(),
        'name' => $name,
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR',
      ]
    );
    $store_exists = (bool) Store::load($store->id());
    $this->assertTrue($store_exists, 'The new store has been created in the database.');

    // Create a store through the form.
    $this->drupalGet('admin/commerce/stores');
    $this->clickLink('Add a new store');
    $this->clickLink($this->type->label());
    $edit = [
      'name[0][value]' => 'Foo Store',
      'mail[0][value]' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'EUR',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Tests updating a store through the edit form.
   */
  public function testUpdateStore() {
    // Create a new store.
    $store = $this->createEntity('commerce_store', [
        'type' => $this->type->id(),
        'name' => $this->randomMachineName(8),
        'email' => \Drupal::currentUser()->getEmail(),
      ]
    );

    $this->drupalGet('admin/commerce/stores');
    $this->clickLink(t('Edit'));
    // Only change the name.
    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $store_changed = Store::load($store->id());
    $this->assertEqual($store->getName(), $store_changed->getName(), 'The name of the store has been changed.');
  }

  /**
   * Tests deleting a store.
   */
  public function testDeleteStore() {
    // Create a new store.
    $store = $this->createEntity('commerce_store', [
        'type' => $this->type->id(),
        'name' => $this->randomMachineName(8),
        'email' => \Drupal::currentUser()->getEmail(),
      ]
    );
    $store_exists = (bool) Store::load($store->id());
    $this->assertTrue($store_exists, 'The new store has been created in the database.');

    // Delete the Store and verify deletion.
    $store->delete();
    $store_exists = (bool) Store::load($store->id());
    $this->assertFalse($store_exists, 'The new store has been deleted from the database.');
  }
}
