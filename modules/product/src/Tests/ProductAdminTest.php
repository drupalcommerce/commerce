<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Tests\ProductTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\Product;

/**
 * Create, view, edit, delete, and change products and product types.
 *
 * @group commerce
 */
class ProductAdminTest extends CommerceProductTestBase {

  /**
   * Tests creating a product via the admin.
   */
  function testAddCommerceProductAdmin() {
    $title = $this->randomMachineName();
    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add a new product');
    $edit = [
      'title[0][value]' => $title,
      'sku[0][value]' => strtolower($this->randomMachineName()),
      'store_id' => $this->commerce_store->id()
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $product = \Drupal::entityQuery('commerce_product')
      ->condition("sku", $edit['sku[0][value]'])
      ->range(0, 1)
      ->execute();
    $product = entity_load("commerce_product", current($product));

    $this->assertNotNull($product, 'The new product has been created in the database.');
    $this->assertText(t("The product @title has been successfully saved.", ['@title' => $title]), "Commerce Product success text is showing");
    $this->assertText($title, 'Created product name exists on this page.');

    // Assert that the frontend product page is displaying.
    $this->drupalGet('product/' . $product->id());
    $this->assertResponse(200);
    $this->assertText($product->getTitle(), "Commerce Product title exists");
  }

  /**
   * Tests creating a product with an existing SKU.
   */
  function testAddCommerceProductExistingSkuAdmin() {
    $product = $this->createEntity(
      'commerce_product', [
        'sku' => $this->randomMachineName(),
        'title' => $this->randomMachineName(),
        'type' => 'product',
        'store_id' => $this->commerce_store->id()
      ]
    );

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add a new product');
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'sku[0][value]' => $product->getSku(),
      'store_id' => $this->commerce_store->id()
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that two products with the same SKU exist.
    $duplicateCommerceProductSkus = \Drupal::entityQuery('commerce_product')
      ->count()
      ->execute();
    $this->assertEqual($duplicateCommerceProductSkus, 1, "Only one product exists");

    $this->assertText("is already in use", "Commerce Product failure text is showing");
  }

  /**
   * Tests deleting a product via the admin.
   */
  function testDeleteCommerceProductAdmin() {
    $product = $this->createEntity(
      'commerce_product', [
        'sku' => $this->randomMachineName(),
        'title' => $this->randomMachineName(),
        'type' => "product"
      ]
    );

    $this->drupalGet('product/' . $product->id() . '/delete');
    $this->assertText(t("Are you sure you want to delete the product @product?", ['@product' => $product->getTitle()]), "Commerce Product deletion confirmation text is showing");
    $this->assertText(t('This action cannot be undone.'), 'The product deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $productExists = (bool) Product::load($product->id());
    $this->assertFalse($productExists, 'The new product has been deleted from the database.');
  }

  /**
   * Tests adding product attributes to a field with just the attribute field checked.
   */
  function testProductAttributesAdmin() {
    $productFields = $this->testAddCommerceProductFieldAdmin();
    $edit = [
      'attribute_field' => 1,
      'attribute_widget_title' => $this->randomMachineName()
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->drupalGet('/admin/commerce/config/product-types/product/edit/fields/commerce_product.product.field_' . $productFields["field_name"]);
    $this->assertFieldChecked("edit-attribute-field", "Product attribute field is checked");
    $this->assertFieldChecked("edit-attribute-widget-select", "Product attribute widget select list field is checked");
    $this->assertField('attribute_widget_title', $edit['attribute_widget_title']);
  }

  /**
   * Tests adding product attributes to a field with the attribute field checked, and changing the radios.
   */
  function testAddProductAttributesFieldsAdmin() {
    $attributeWidgets = ['select', 'radios'];
    foreach ($attributeWidgets as $attributeWidget) {
      $productFields = $this->testAddCommerceProductFieldAdmin();
      $edit = [
        'attribute_field' => 1,
        'attribute_widget' => $attributeWidget,
        'attribute_widget_title' => $this->randomMachineName()
      ];
      $this->drupalPostForm(NULL, $edit, t('Save settings'));
      // Go back to the URL by clicking "Edit"
      $this->drupalGet('/admin/commerce/config/product-types/product/edit/fields/commerce_product.product.field_' . $productFields["field_name"]);

      $this->assertFieldChecked("edit-attribute-field", "Product attribute field is checked");
      $this->assertFieldChecked("edit-attribute-widget-" . $attributeWidget, "Product attribute widget select list field is checked");
      $this->assertField('attribute_widget_title', $edit['attribute_widget_title']);
    }
  }

  /**
   * Tests adding product fields.
   */
  protected function testAddCommerceProductFieldAdmin() {
    $this->drupalGet('admin/commerce/config/product-types/product/edit/fields/add-field');

    // Create a new field.
    $fields = [
      'label' => $label = $this->randomMachineName(),
      'field_name' => $name = strtolower($this->randomMachineName()),
      'new_storage_type' => 'list_string',
    ];
    $this->drupalPostForm('admin/commerce/config/product-types/product/edit/fields/add-field', $fields, t('Save and continue'));

    $edit = ['settings[allowed_values]' => '1|1\n2|2'];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));

    return $fields;
  }
}
