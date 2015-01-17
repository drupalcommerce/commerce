<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Tests\CommerceProductTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\CommerceProduct;

/**
 * Create, view, edit, delete, and change products and product types.
 *
 * @group commerce
 */
class CommerceProductAdminTest extends CommerceProductTestBase {

  /**
   * Tests creating a product via the admin.
   */
  function testAddCommerceProductAdmin() {
    $title = $this->randomMachineName();

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add a new product');
    $edit = array(
      'title[0][value]' => $title,
      'sku[0][value]' => strtolower($this->randomMachineName()),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $product = \Drupal::entityQuery('commerce_product')
      ->condition("sku", $edit['sku[0][value]'])
      ->range(0, 1)
      ->execute();

    $product = entity_load("commerce_product", current($product));

    $this->assertTrue($product, 'The new product has been created in the database.');
    $this->assertText(t("The product @title has been successfully saved.", array('@title' => $title)), "Commerce Product success text is showing");
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
    $product = $this->createEntity('commerce_product', array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'product'
    ));

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add a new product');
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'sku[0][value]' => $product->getSku(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that two products with the same SKU exist.
    $duplicate_commerce_product_skus = \Drupal::entityQuery('commerce_product')
      ->count()
      ->execute();
    $this->assertEqual($duplicate_commerce_product_skus, 1, "Only one product exists");

    $this->assertText("is already in use", "Commerce Product failure text is showing");
  }

  /**
   * Tests deleting a product via the admin.
   */
  function testDeleteCommerceProductAdmin() {
    $product = $this->createEntity('commerce_product', array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => "product"
    ));

    $this->drupalGet('product/' . $product->id() . '/delete');
    $this->assertText(t("Are you sure you want to delete the product @product?", array('@product' => $product->getTitle())), "Commerce Product deletion confirmation text is showing");
    $this->assertText(t('This action cannot be undone.'), 'The product deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $product_exists = (bool) CommerceProduct::load($product->id());
    $this->assertFalse($product_exists, 'The new product has been deleted from the database.');
  }

  /**
   * Tests adding product attributes to a field with just the attribute field checked.
   */
  function testProductAttributesAdmin() {
    $this->testAddCommerceProductFieldAdmin();
    $edit = array(
      'field[commerce_product][attribute_field]' => 1,
      'field[commerce_product][attribute_widget_title]' => $this->randomMachineName()
    );
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertFieldChecked("edit-field-commerce-product-attribute-field", "Product attribute field is checked");
    $this->assertFieldChecked("edit-field-commerce-product-attribute-widget-select", "Product attribute widget select list field is checked");
    $this->assertField('field[commerce_product][attribute_widget_title]', $edit['field[commerce_product][attribute_widget_title]']);
  }

  /**
   * Tests adding product attributes to a field with the attribute field checked, and changing the radios.
   */
  function testAddProductAttributesFieldsAdmin() {
    $attribute_widgets = array("select", "radios");
    foreach ($attribute_widgets as $attribute_widget) {
      $this->testAddCommerceProductFieldAdmin();
      $edit = array(
        'field[commerce_product][attribute_field]' => 1,
        'field[commerce_product][attribute_widget]' => $attribute_widget,
        'field[commerce_product][attribute_widget_title]' => $this->randomMachineName()
      );
      $this->drupalPostForm(NULL, $edit, t('Save settings'));
      $this->assertFieldChecked("edit-field-commerce-product-attribute-field", "Product attribute field is checked");
      $this->assertFieldChecked("edit-field-commerce-product-attribute-widget-" . $attribute_widget, "Product attribute widget select list field is checked");
      $this->assertField('field[commerce_product][attribute_widget_title]', $edit['field[commerce_product][attribute_widget_title]']);
    }
  }

  /**
   * Tests adding product fields.
   */
  function testAddCommerceProductFieldAdmin() {
    $this->drupalGet('admin/commerce/config/product-types/product/edit/fields/add-field');

    // Create a new field.
    $edit = array(
      'label' => $label = $this->randomMachineName(),
      'field_name' => $name = strtolower($this->randomMachineName()),
      'new_storage_type' => 'list_string',
    );
    $this->drupalPostForm('admin/commerce/config/product-types/product/edit/fields/add-field', $edit, t('Save and continue'));

    $edit = array('field_storage[settings][allowed_values]' => '1|1\n2|2');
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
  }
}
