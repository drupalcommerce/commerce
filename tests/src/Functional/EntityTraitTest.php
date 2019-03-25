<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\commerce_store\Entity\StoreType;

/**
 * Tests the entity trait functionality.
 *
 * @group commerce
 */
class EntityTraitTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'telephone',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_store_type',
      'administer commerce_store fields',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests the trait functionality.
   */
  public function testTraits() {
    $this->drupalGet('admin/commerce/config/store-types/online/edit');

    $this->assertSession()->fieldExists('traits[first]');
    $this->assertSession()->fieldExists('traits[second]');
    $this->assertSession()->checkboxNotChecked('traits[first]');
    $this->assertSession()->checkboxNotChecked('traits[second]');

    $edit = [
      'traits[first]' => 'first',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/commerce/config/store-types/online/edit');
    $this->assertSession()->checkboxChecked('traits[first]');
    $this->assertSession()->checkboxNotChecked('traits[second]');
    // The store type entity shows the correct traits.
    $store_type = StoreType::load('online');
    $this->assertEquals(['first'], $store_type->getTraits());
    $this->submitForm($edit, t('Save'));
    // The field was created.
    $this->drupalGet('admin/commerce/config/store-types/online/edit/fields');
    $this->assertSession()->pageTextContains('phone');

    $this->drupalGet('admin/commerce/config/store-types/online/edit');
    $edit = [
      'traits[first]' => 'first',
      'traits[second]' => 'second',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The Second trait is in conflict with the following traits: First.');

    $edit = [
      'traits[first]' => FALSE,
      'traits[second]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/commerce/config/store-types/online/edit');
    $this->assertSession()->checkboxNotChecked('traits[first]');
    $this->assertSession()->checkboxNotChecked('traits[second]');
    // The store type entity shows the correct traits.
    $store_type = StoreType::load('online');
    $this->assertEquals([], $store_type->getTraits());
    $this->submitForm($edit, t('Save'));
    // The field was removed.
    $this->drupalGet('admin/commerce/config/store-types/online/edit/fields');
    $this->assertSession()->pageTextNotContains('phone');
  }

  /**
   * Tests the trait functionality on the duplicate form.
   */
  public function testDuplicateTraits() {
    $this->drupalGet('admin/commerce/config/store-types/online/edit');
    $edit = [
      'traits[first]' => 'first',
      'traits[second]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');

    $this->drupalGet('admin/commerce/config/store-types/online/duplicate');
    $this->assertSession()->checkboxChecked('traits[first]');
    $this->assertSession()->checkboxNotChecked('traits[second]');
    $edit = [
      'label' => 'Online2',
      'id' => 'online2',
      'traits[first]' => FALSE,
      'traits[second]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Online2 store type.');

    $store_type = StoreType::load('online2');
    $this->assertEquals([], $store_type->getTraits());
    // The field was removed.
    $this->drupalGet('admin/commerce/config/store-types/online2/edit/fields');
    $this->assertSession()->pageTextNotContains('phone');
  }

}
