<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Ensure the product variation type works correctly.
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
  }

  /**
   * Tests whether the default product variation type was created.
   */
  public function testDefaultProductVariationType() {
    $variation_type = ProductVariationType::load('default');
    $this->assertNotEmpty(!empty($variation_type), 'The default product variation type is available.');

    $this->drupalGet('admin/commerce/config/product-variation-types');
    $rows = $this->getSession()->getPage()->find('css', 'table tbody tr');
    $this->assertEquals(count($rows), 1, '1 product variation type is correctly listed.');
  }

  /**
   * Tests creating a product variation type programmatically and via a form.
   */
  public function testProductVariationTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'orderItemType' => 'default',
    ];
    $this->createEntity('commerce_product_variation_type', $values);
    $variation_type = ProductVariationType::load($values['id']);
    $this->assertEquals($variation_type->label(), $values['label'], 'The new product variation type has the correct label.');
    $this->assertEquals($variation_type->getOrderItemTypeId(), $values['orderItemType'], 'The new product variation type has the correct order item type.');

    $this->drupalGet('admin/commerce/config/product-variation-types/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'orderItemType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $variation_type = ProductVariationType::load($edit['id']);
    $this->assertNotEmpty(!empty($variation_type), 'The new product variation type has been created.');
    $this->assertEquals($variation_type->label(), $edit['label'], 'The new product variation type has the correct label.');
    $this->assertEquals($variation_type->getOrderItemTypeId(), $edit['orderItemType'], 'The new product variation type has the correct order item type.');
  }

  /**
   * Tests editing a product variation type using the UI.
   */
  public function testProductVariationTypeEditing() {
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'label' => 'Default2',
    ];
    $this->submitForm($edit, t('Save'));
    $variation_type = ProductVariationType::load('default');
    $this->assertEquals($variation_type->label(), 'Default2', 'The label of the product variation type has been changed.');
  }

  /**
   * Tests deleting a product variation type via a form.
   */
  public function testProductVariationTypeDeletion() {
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo',
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
    ]);

    // @todo Make sure $variation_type->delete() also does nothing if there's
    // a variation of that type. Right now the check is done on the form level.
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variation_type->id() . '/delete');
    $this->assertSession()->pageTextContains(
      t('@type is used by 1 product variation on your site. You cannot remove this product variation type until you have removed all of the @type product variations.', ['@type' => $variation_type->label()]),
      'The product variation type will not be deleted until all variations of that type are deleted.'
    );
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'), 'The product variation type deletion confirmation form is not available');

    $variation->delete();
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variation_type->id() . '/delete');
    $this->assertSession()->pageTextContains(
      t('Are you sure you want to delete the product variation type @type?', ['@type' => $variation_type->label()]),
      'The product variation type is available for deletion'
    );
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->getSession()->getPage()->pressButton('Delete');
    $exists = (bool) ProductVariationType::load($variation_type->id());
    $this->assertEmpty($exists, 'The new product variation type has been deleted from the database.');
  }

  /**
   * Tests the attributes element on the product variation type form.
   */
  public function testProductVariationTypeAttributes() {
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'label' => 'Default',
      'orderItemType' => 'default',
      'attributes[color]' => 'color',
    ];
    $this->submitForm($edit, t('Save'));
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextContains('attribute_color', 'The color attribute field has been created');

    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'label' => 'Default',
      'orderItemType' => 'default',
      'attributes[color]' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextNotContains('attribute_color', 'The color attribute field has been deleted');
  }

}
