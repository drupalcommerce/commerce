<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\ProductTypeTest
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\ProductType;

/**
 * Ensure the product type works correctly.
 *
 * @group commerce
 */
class ProductTypeAdminTest extends CommerceProductTestBase {

  /**
   * Tests if the default Product Type was created.
   */
  public function testDefaultProductTypeAdmin() {
    $this->drupalGet('admin/commerce/config/product-types');
    $productTypes = ProductType::loadMultiple();

    $this->assertTrue(isset($productTypes['product']), 'Found the product type "Product"');

    $productType = ProductType::load('product');
    $this->assertEqual($productTypes['product'], $productType, 'The correct product type is loaded');
  }

  /**
   * Tests if the correct number of Product Types are being listed.
   */
  public function testListProductTypeAdmin() {
    $title = strtolower($this->randomMachineName(8));
    $tableSelector = 'table tbody tr';

    // The product shows one default product type.
    $this->drupalGet('admin/commerce/config/product-types');

    $productTypes = $this->cssSelect($tableSelector);
    $this->assertEqual(count($productTypes), 1, '1 Products types are correctly listed');

    // Create a new product type entity and see if the list has two product types.
    $this->createEntity('commerce_product_type', array(
        'id' => $title,
        'label' => $title
      )
    );

    $this->drupalGet('admin/commerce/config/product-types');
    $productTypes = $this->cssSelect($tableSelector);
    $this->assertEqual(count($productTypes), 2, '2 Products types are correctly listed');
  }

  /**
   * Tests creating a Product Type programaticaly and through the create form.
   */
  public function testCreateProductTypeAdmin() {
    $title = strtolower($this->randomMachineName(8));

    // Create a product type programmaticaly.
    $type = $this->createEntity('commerce_product_type', array(
        'id' => $title,
        'label' => $title,
      )
    );

    $typeExists = (bool) ProductType::load($type->id());
    $this->assertTrue($typeExists, 'The new product type has been created in the database.');

    $this->drupalGet('admin/commerce/config/product-types/add');

    // Create a product type through the form.
    $edit = array(
      'label' => 'foo',
      'id' => 'foo'
    );
    $this->drupalPostForm('admin/commerce/config/product-types/add', $edit, t('Save'));
    $typeExists = (bool) ProductType::load($edit['label']);
    $this->assertTrue($typeExists, 'The new product type has been created in the database.');
  }

  /**
   * Tests updating a Product Type through the edit form.
   */
  public function testUpdateProductTypeAdmin() {
    // Create a new product type.
    $productType = $this->createEntity('commerce_product_type', array(
        'id' => 'foo',
        'label' => 'Label for foo'
      )
    );

    // Only change the label.
    $edit = array(
      'label' => $this->randomMachineName(8),
    );
    $this->drupalPostForm('admin/commerce/config/product-types/' . $productType->id() . '/edit', $edit, 'Save');
    $productTypeChanged = ProductType::load($productType->id());
    $this->assertNotEqual($productType->label(), $productTypeChanged->label(), 'The label of the product type has been changed.');
  }

  /**
   * Tests deleting a Product Type through the form.
   */
  public function testDeleteProductTypeAdmin() {
    // Create a product type programmaticaly.
    $type = $this->createEntity('commerce_product_type', array(
        'id' => 'foo',
        'label' => 'foo'
      )
    );

    // Create a product.
    $values = array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => $type->id()
    );
    $product = $this->createEntity('commerce_product', $values);

    // Try to delete the product type.
    $this->drupalGet('admin/commerce/config/product-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 product on your site. You can not remove this product type until you have removed all of the %type products.', array('%type' => $type->label())),
      'The product type will not be deleted until all products of that type are deleted'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The product type deletion confirmation form is not available');

    // Deleting the product type when its not being referenced by a product.
    $product->delete();
    $this->drupalGet('admin/commerce/config/product-types/' . $type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the product type %type?', array('%type' => $type->label())),
      'The product type is available for deletion'
    );
    $this->assertText(t('This action cannot be undone.'), 'The product type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $typeExists = (bool) ProductType::load($type->id());
    $this->assertFalse($typeExists, 'The new product type has been deleted from the database.');

  }
}
