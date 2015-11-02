<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Create, view, edit, delete, and change products and product types.
 *
 * @group commerce
 */
class ProductAdminTest extends ProductTestBase {

  /**
   * Tests creating a product via the admin.
   */
  function testAddProductAdmin() {
    $title = $this->randomMachineName();
    $storeIds = array_map(function ($store) {
      return $store->id();
    }, $this->stores);

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add product');
    $productVariationValues = [
      'variations[form][inline_entity_form][sku][0][value]' => $this->randomMachineName(),
      'variations[form][inline_entity_form][status][value]' => 1
    ];
    $this->drupalPostForm(NULL, $productVariationValues, t('Create entity'));

    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($storeIds as $storeId) {
      $edit['stores['. $storeId .']'] = $storeId;
    }
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $result = \Drupal::entityQuery('commerce_product')
      ->condition("title", $edit['title[0][value]'])
      ->range(0, 1)
      ->execute();
    $productId = reset($result);
    $product = Product::load($productId);

    $this->assertNotNull($product, 'The new product has been created in the database.');
    $this->assertText(t("The product @title has been successfully saved.", ['@title' => $title]), "Commerce Product success text is showing");
    $this->assertText($title, 'Created product name exists on this page.');
    $this->assertFieldValues($product->getStores(), $this->stores, 'Created product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $storeIds, 'Created product has the correct associated store ids.');

    // Assert that the frontend product page is displaying.
    $this->drupalGet('product/' . $product->id());
    $this->assertResponse(200);
    $this->assertText($product->getTitle(), 'Product title exists');

    // Test product variations
    $productVariation = \Drupal::entityQuery('commerce_product_variation')
      ->condition("sku", $productVariationValues['variations[form][inline_entity_form][sku][0][value]'])
      ->range(0, 1)
      ->execute();

    $productVariation = ProductVariation::load(current($productVariation));
    $this->assertNotNull($productVariation, 'The new product variation has been created in the database.');
  }

  /**
   * Tests adding/removing stores to a product via the admin.
   */
  function testUpdateProductAdmin() {
    $storeIds = array_map(function ($store) {
      return $store->id();
    }, $this->stores);

    // Create a product with one (the first created) store.
    $product = $this->createEntity('commerce_product', [
      'title' => $this->randomMachineName(),
      'type' => 'default',
      'stores' => [$this->stores[0]],
    ]);
    $formUrl = 'product/' . $product->id() . '/edit';
    $this->drupalGet($formUrl);
    $this->assertResponse(200, 'Product edit form can be accessed at ' . $formUrl);

    // Add some variations.
    $productVariationValues = [
      'variations[form][inline_entity_form][sku][0][value]' => $this->randomMachineName(),
      'variations[form][inline_entity_form][status][value]' => 1
    ];
    $this->drupalPostForm(NULL, $productVariationValues, t('Create entity'));

    $edit = [];
    // Now add the rest of the created stores, all of them.
    foreach ($storeIds as $storeId) {
      $edit['stores['. $storeId .']'] = $storeId;
    }
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    \Drupal::entityManager()->getStorage('commerce_product')->resetCache();
    $product = Product::load($product->id());
    $this->assertFieldValues($product->getStoreIds(), $storeIds, 'The product belongs to all created stores.');

    // Try to uncheck all stores from the form.
    // It should be denied and no stores should be deleted from the product.
    $edit = [];
    foreach ($storeIds as $storeId) {
      $edit['stores['. $storeId .']'] = FALSE;
    }
    $this->drupalPostForm($formUrl, $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertRaw('Stores field is required.', 'Stores field is required, cannot delete all stores from the product.');
    \Drupal::entityManager()->getStorage('commerce_product')->resetCache();
    $product = Product::load($product->id());
    $this->assertFieldValues($product->getStoreIds(), $storeIds, 'There are no stores deleted from the product.');
  }

  /**
   * Tests deleting a product via the admin.
   */
  function testDeleteProductAdmin() {
    $product = $this->createEntity('commerce_product', [
      'title' => $this->randomMachineName(),
      'type' => 'default',
    ]);
    $this->drupalGet('product/' . $product->id() . '/delete');
    $this->assertText(t("Are you sure you want to delete the product @product?", ['@product' => $product->getTitle()]), "Commerce Product deletion confirmation text is showing");
    $this->assertText(t('This action cannot be undone.'), 'The product deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    \Drupal::entityManager()->getStorage('commerce_product')->resetCache();

    $productExists = (bool) Product::load($product->id());
    $this->assertFalse($productExists, 'The new product has been deleted from the database.');
  }

  /**
   * Tests that anonymous users cannot see the admin/commerce/products page.
   */
  protected function testAdminProducts() {
    // First test that the current admin user can see the page
    $this->drupalGet('admin/commerce/products');
    $this->assertResponse(200);
    $this->assertNoText("You are not authorized to access this page.");
    $this->assertLink("Add product");

    // Logout and check that anonymous users cannot see the products page
    // and receieve a 403 error code.
    $this->drupalLogout();

    $this->drupalGet('admin/commerce/products');
    $this->assertResponse(403);
    $this->assertText("You are not authorized to access this page.");
    $this->assertNoLink("Add product");
  }

}
