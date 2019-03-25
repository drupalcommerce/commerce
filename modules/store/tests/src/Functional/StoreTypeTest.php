<?php

namespace Drupal\Tests\commerce_store\Functional;

use Drupal\commerce_store\Entity\StoreType;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the store type UI.
 *
 * @group commerce
 */
class StoreTypeTest extends CommerceBrowserTestBase {

  /**
   * Tests whether the default store type was created.
   */
  public function testDefault() {
    $store_type = StoreType::load('online');
    $this->assertNotEmpty($store_type);

    $this->drupalGet('admin/commerce/config/store-types');
    $rows = $this->getSession()->getPage()->findAll('css', 'table tbody tr');
    $this->assertCount(1, $rows);
  }

  /**
   * Tests adding a store type.
   */
  public function testAdd() {
    $this->drupalGet('admin/commerce/config/store-types/add');
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Foo store type.');

    $store_type = StoreType::load($edit['id']);
    $this->assertNotEmpty($store_type);
    $this->assertEquals('Foo', $store_type->label());
  }

  /**
   * Tests editing a store type.
   */
  public function testEdit() {
    $this->drupalGet('admin/commerce/config/store-types/online/edit');
    $edit = [
      'label' => 'Online!',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Online! store type.');

    $store_type = StoreType::load('online');
    $this->assertEquals($edit['label'], $store_type->label());
  }

  /**
   * Tests duplicating a store type.
   */
  public function testDuplicate() {
    $this->drupalGet('admin/commerce/config/store-types/online/duplicate');
    $this->assertSession()->fieldValueEquals('label', 'Online');
    $edit = [
      'label' => 'Online2',
      'id' => 'online2',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Online2 store type.');

    // Confirm that the original store type is unchanged.
    $store_type = StoreType::load('online');
    $this->assertNotEmpty($store_type);
    $this->assertEquals('Online', $store_type->label());

    // Confirm that the new store type has the expected data.
    $store_type = StoreType::load('online2');
    $this->assertNotEmpty($store_type);
    $this->assertEquals('Online2', $store_type->label());
  }

  /**
   * Tests deleting a product type.
   */
  public function testDelete() {
    /** @var \Drupal\commerce_store\Entity\StoreTypeInterface $store_type */
    $store_type = $this->createEntity('commerce_store_type', [
      'id' => 'foo',
      'label' => 'Label for foo',
    ]);
    $store = $this->createStore(NULL, NULL, $store_type->id());

    // Confirm that the type can't be deleted while there's a store.
    $this->drupalGet($store_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('@type is used by 1 store on your site. You cannot remove this store type until you have removed all of the @type stores.', ['@type' => $store_type->label()]));
    $this->assertSession()->pageTextNotContains('This action cannot be undone.');
    $this->assertSession()->pageTextNotContains('The store type deletion confirmation form is not available');

    // Confirm that the delete page is not available when the type is locked.
    $store_type->lock();
    $store_type->save();
    $this->drupalGet($store_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals('403');

    // Delete the store, unlock the type, confirm that deletion works.
    $store->delete();
    $store_type->unlock();
    $store_type->save();
    $this->drupalGet($store_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the store type @type?', ['@type' => $store_type->label()]));
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');
    $store_type_exists = (bool) StoreType::load($store_type->id());
    $this->assertEmpty($store_type_exists);
  }

}
