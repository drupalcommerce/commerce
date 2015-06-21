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
class ProductTest extends CommerceProductTestBase {

  /**
   * Tests creating a product.
   */
  function testAddCommerceProduct() {
    $this->createEntity('commerce_product', [
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'product'
    ]);
  }

  /**
   * Tests creating a product with an existing SKU.
   */
  function testAddCommerceProductExistingSku() {
    $values = [
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'product',
      'store_id' => $this->commerce_store->id()
    ];
    $this->createEntity('commerce_product', $values);

    $productDuplicate = Product::create($values);

    $violations = $productDuplicate->sku->validate();
    $this->assertNotEqual($violations->count(), 0, 'Validation fails when creating a product with the same SKU.');
  }

  /**
   * Tests deleting a product.
   */
  function testDeleteProduct() {
    // Create a new product.
    $values = [
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => "product"
    ];
    $product = $this->createEntity('commerce_product', $values);
    $productExists = (bool) Product::load($product->id());
    $this->assertTrue($productExists, 'The new product has been created in the database.');

    // Delete the product and verify deletion.
    $product->delete();

    $productExists = (bool) Product::load($product->id());
    $this->assertFalse($productExists, 'The new product has been deleted from the database.');
  }
}
