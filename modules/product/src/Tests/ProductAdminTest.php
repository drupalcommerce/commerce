<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Create, view, edit, delete, and change products.
 *
 * @group commerce
 */
class ProductAdminTest extends ProductTestBase {

  /**
   * Tests creating a product.
   */
  function testCreateProduct() {
    $title = $this->randomMachineName();
    $store_ids = array_map(function ($store) {
      return $store->id();
    }, $this->stores);

    $this->drupalGet('admin/commerce/products');
    $this->clickLink('Add product');

    // Check the integrity of the add form.
    $this->assertFieldByName('title[0][value]', NULL, 'Title field is present');
    $this->assertFieldByName('variations[form][inline_entity_form][sku][0][value]', NULL, 'SKU field is present');
    $this->assertFieldByName('variations[form][inline_entity_form][price][0][amount]', NULL, 'Price field is present');
    $this->assertFieldByName('variations[form][inline_entity_form][status][value]', NULL, 'Status field is present');
    $this->assertFieldsByValue(t('Create variation'), NULL, 'Create variation button is present');

    // Submit product variation values.
    $variation_sku = $this->randomMachineName();
    $variations_edit = [
      'variations[form][inline_entity_form][sku][0][value]' => $variation_sku,
      'variations[form][inline_entity_form][price][0][amount]' => '9.99',
      'variations[form][inline_entity_form][status][value]' => 1,
    ];
    $this->drupalPostForm(NULL, $variations_edit, t('Create variation'));

    // Submit product values.
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value]['. $store_id .']'] = $store_id;
    }
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Test results.
    // Test product.
    $result = \Drupal::entityQuery('commerce_product')
      ->condition("title", $edit['title[0][value]'])
      ->range(0, 1)
      ->execute();
    $product_id = reset($result);
    $product = Product::load($product_id);

    $this->assertNotNull($product, 'The new product has been created in the database.');
    $this->assertText(t('The product @title has been successfully saved', ['@title' => $title]), 'Product success text is shown');
    $this->assertText($title, 'Created product name exists on this page.');
    $this->assertFieldValues($product->getStores(), $this->stores, 'Created product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $store_ids, 'Created product has the correct associated store ids.');

    // Assert that the frontend product page is displaying.
    $this->drupalGet($product->toUrl('canonical'));
    $this->assertResponse(200);
    $this->assertText($product->getTitle(), 'Product title exists');

    // Test product variations.
    $variation = \Drupal::entityQuery('commerce_product_variation')
      ->condition('sku', $variation_sku)
      ->range(0, 1)
      ->execute();

    $variation = ProductVariation::load(current($variation));
    $this->assertNotNull($variation, 'The new product variation has been created in the database.');
  }

  /**
   * Tests editing a product.
   */
  function testEditProduct() {
    // Create product and product variation entities.
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'variations' => [$variation],
    ]);

    // Assert that the frontend product page is displaying.
    $this->drupalGet($product->toUrl('edit-form'));
    $this->assertResponse(200, 'The product edit form can be accessed.');

    // Check the integrity of the edit form.
    $this->assertFieldByName('title[0][value]', NULL, 'Title field is present');
    $this->assertFieldById('edit-variations-entities-0-actions-ief-entity-edit', NULL, 'The edit button for product variation is present');
    $this->drupalPostForm(NULL, [], t('Edit'));

    // Check the product variation edit form.
    $this->assertFieldByName('variations[form][inline_entity_form][entities][0][form][sku][0][value]', NULL, 'SKU field is present');
    $this->assertFieldByName('variations[form][inline_entity_form][entities][0][form][price][0][amount]', NULL, 'Price field is present');
    $this->assertFieldByName('variations[form][inline_entity_form][entities][0][form][status][value]', NULL, 'Status field is present');
    $this->assertFieldsByValue(t('Update variation'), NULL, 'Update variation button is present');

    // Submit product variation values.
    $new_sku = strtolower($this->randomMachineName());
    $new_price_amount = '1.11';
    $variations_edit = [
      'variations[form][inline_entity_form][entities][0][form][sku][0][value]' => $new_sku,
      'variations[form][inline_entity_form][entities][0][form][price][0][amount]' => $new_price_amount,
      'variations[form][inline_entity_form][entities][0][form][status][value]' => 1,
    ];
    $this->drupalPostForm(NULL, $variations_edit, t('Update variation'));

    // Submit product values.
    $title = $this->randomMachineName();
    $store_ids = array_map(function ($store) {
      return $store->id();
    }, $this->stores);
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value]['. $store_id .']'] = $store_id;
    }
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));

    // Test results.
    \Drupal::service('entity_type.manager')->getStorage('commerce_product_variation')->resetCache([$variation->id()]);
    $variation = ProductVariation::load($variation->id());
    $this->assertEqual($variation->getSku(), $new_sku, 'The variation sku successfully updated.');
    $this->assertEqual($variation->get('price')->amount, $new_price_amount, 'The variation price successfully updated.');
    \Drupal::service('entity_type.manager')->getStorage('commerce_product')->resetCache([$product->id()]);
    $product = Product::load($product->id());
    $this->assertEqual($product->getTitle(), $title, 'The product title successfully updated.');
    $this->assertFieldValues($product->getStores(), $this->stores, 'Updated product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $store_ids, 'Updated product has the correct associated store ids.');
  }

  /**
   * Tests deleting a product.
   */
  function testDeleteProduct() {
    $product = $this->createEntity('commerce_product', [
      'title' => $this->randomMachineName(),
      'type' => 'default',
    ]);
    $this->drupalGet($product->toUrl('delete-form'));
    $this->assertText(t("Are you sure you want to delete the product @product?", ['@product' => $product->getTitle()]), "Commerce Product deletion confirmation text is showing");
    $this->assertText(t('This action cannot be undone.'), 'The product deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    // Test results
    \Drupal::service('entity_type.manager')->getStorage('commerce_product')->resetCache();
    $product_exists = (bool) Product::load($product->id());
    $this->assertFalse($product_exists, 'The new product has been deleted from the database.');
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
    // and receive a 403 error code.
    $this->drupalLogout();

    $this->drupalGet('admin/commerce/products');
    $this->assertResponse(403);
    $this->assertText("You are not authorized to access this page.");
    $this->assertNoLink("Add product");
  }

}
