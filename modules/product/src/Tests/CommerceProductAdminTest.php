<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Tests\CommerceProductTest.
 */

namespace Drupal\commerce_product\Tests;

/**
 * Create, view, edit, delete, and change products and product types.
 *
 * @group commerce
 */
class CommerceProductAdminTest extends CommerceProductTestBase {

  /**
   * Tests creating a commerce product via the admin.
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
    $commerce_product = \Drupal::entityQuery('commerce_product')
      ->condition("sku", $edit['sku[0][value]'])
      ->range(0, 1)
      ->execute();

    $commerce_product = entity_load("commerce_product", current($commerce_product));

    $this->assertTrue($commerce_product, 'The new commerce product has been created in the database.');
    $this->assertText(t("The product @title has been successfully saved.", array('@title' => $title)), "Commerce Product success text is showing");
    $this->assertText($title, 'Created product name exists on this page.');

    // Assert that the frontend commerce product page is displaying.
    $this->drupalGet('product/' . $commerce_product->id());
    $this->assertResponse(200);
    $this->assertText($commerce_product->getTitle(), "Commerce Product title exists");
  }

  /**
   * Tests creating a commerce product with an existing SKU.
   */
  function testAddCommerceProductExistingSkuAdmin() {
    $commerce_product = $this->createEntity('commerce_product', array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'type'
    ));

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add a new product');
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'sku[0][value]' => $commerce_product->getSku(),
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that two products with the same SKU exist.
    $duplicate_commerce_product_skus = \Drupal::entityQuery('commerce_product')
      ->count()
      ->execute();
    $this->assertEqual($duplicate_commerce_product_skus, 1, "Only one commerce product exists");

    $this->assertText("is already in use", "Commerce Product failure text is showing");
  }
}
