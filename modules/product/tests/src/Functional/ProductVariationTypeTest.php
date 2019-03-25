<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests the product variation type UI.
 *
 * @group commerce
 */
class ProductVariationTypeTest extends ProductBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    $this->createEntity('commerce_product_attribute', [
      'id' => 'size',
      'label' => 'Size',
    ]);
  }

  /**
   * Tests whether the default product variation type was created.
   */
  public function testDefault() {
    $variation_type = ProductVariationType::load('default');
    $this->assertNotEmpty($variation_type);

    $this->drupalGet('admin/commerce/config/product-variation-types');
    $rows = $this->getSession()->getPage()->findAll('css', 'table tbody tr');
    $this->assertCount(1, $rows);
  }

  /**
   * Tests adding a product variation type.
   */
  public function testAdd() {
    $this->drupalGet('admin/commerce/config/product-variation-types/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => 'Clothing',
      'orderItemType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Clothing product variation type.');

    $variation_type = ProductVariationType::load($edit['id']);
    $this->assertNotEmpty($variation_type);
    $this->assertEquals('Clothing', $variation_type->label());
    $this->assertEquals('default', $variation_type->getOrderItemTypeId());
  }

  /**
   * Tests editing a product variation type.
   */
  public function testEdit() {
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'label' => 'Default2',
      'attributes[color]' => 'color',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Default2 product variation type.');

    $variation_type = ProductVariationType::load('default');
    $this->assertEquals('Default2', $variation_type->label());
    // Confirm that the attribute field has been created.
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextContains('attribute_color');

    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'label' => 'Default2',
      'attributes[color]' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Default2 product variation type.');

    // Confirm that the attribute field has been deleted.
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextNotContains('attribute_color');
  }

  /**
   * Tests duplicating a product variation type.
   */
  public function testDuplicate() {
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'attributes[color]' => 'color',
      'attributes[size]' => 'size',
    ];
    $this->submitForm($edit, t('Save'));

    $this->drupalGet('admin/commerce/config/product-variation-types/default/duplicate');
    $this->assertSession()->fieldValueEquals('label', 'Default');
    $this->assertSession()->checkboxChecked('attributes[color]');
    $this->assertSession()->checkboxChecked('attributes[size]');

    $edit = [
      'label' => 'Default2',
      'id' => 'default2',
      'attributes[color]' => 'color',
      'attributes[size]' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Default2 product variation type.');

    // Confirm that the original variation type is unchanged.
    $variation_type = ProductVariationType::load('default');
    $this->assertNotEmpty($variation_type);
    $this->assertEquals('Default', $variation_type->label());

    // Confirm that the new variation type has the expected data.
    $variation_type = ProductVariationType::load('default2');
    $this->assertNotEmpty($variation_type);
    $this->assertEquals('Default2', $variation_type->label());
    $this->assertEquals('default', $variation_type->getOrderItemTypeId());
    // Confirm that only the attribute from the duplicate form was created.
    $this->drupalGet('admin/commerce/config/product-variation-types/default2/edit/fields');
    $this->assertSession()->pageTextContains('attribute_color');
    $this->assertSession()->pageTextNotContains('attribute_size');
  }

  /**
   * Tests deleting a product variation type.
   */
  public function testDelete() {
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $variation_type */
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo',
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
    ]);

    // Confirm that the type can't be deleted while there's a variation.
    $this->drupalGet($variation_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('@type is used by 1 product variation on your site. You cannot remove this product variation type until you have removed all of the @type product variations.', ['@type' => $variation_type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Confirm that the delete page is not available when the type is locked.
    $variation_type->lock();
    $variation_type->save();
    $this->drupalGet($variation_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals('403');

    // Delete the variation, unlock the type, confirm that deletion works.
    $variation->delete();
    $variation_type->unlock();
    $variation_type->save();
    $this->drupalGet($variation_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the product variation type @type?', ['@type' => $variation_type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->getSession()->getPage()->pressButton('Delete');
    $exists = (bool) ProductVariationType::load($variation_type->id());
    $this->assertEmpty($exists);
  }

}
