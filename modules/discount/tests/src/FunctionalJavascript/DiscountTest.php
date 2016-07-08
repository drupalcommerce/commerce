<?php

namespace Drupal\Tests\commerce_discount\FunctionalJavascript;

use Drupal\commerce_discount\Entity\Discount;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Create, view, edit, delete, and change discount entities.
 *
 * @group commerce
 */
class DiscountTest extends CommerceBrowserTestBase {

  use StoreCreationTrait;
  use JavascriptTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'commerce_discount'];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer discounts',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a discount.
   */
  public function testCreateDiscount() {
    $this->drupalGet('admin/commerce/discounts');
    $this->getSession()->getPage()->clickLink('Add a new discount');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('name[0][value]');

    $name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $name,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name discount.");
    $discount_count = $this->getSession()->getPage()->find('css', '.view-commerce-discounts tr td.views-field-name');
    $this->assertEquals(count($discount_count), 1, 'Discounts exists in the table.');
  }

  /**
   * Tests editing a discount.
   */
  public function testEditDiscount() {
    $discount = $this->createEntity('commerce_discount', [
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($discount->toUrl('edit-form'));
    $new_discount_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_discount_name,
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_discount')->resetCache([$discount->id()]);
    $discount_changed = Discount::load($discount->id());
    $this->assertEquals($new_discount_name, $discount_changed->getName(), 'The discount name successfully updated.');
  }

  /**
   * Tests deleting a discount.
   */
  public function testDeleteDiscount() {
    $discount = $this->createEntity('commerce_discount', [
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($discount->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_discount')->resetCache([$discount->id()]);
    $discount_exists = (bool) Discount::load($discount->id());
    $this->assertFalse($discount_exists, 'The new discount has been deleted from the database using UI.');
  }

}
