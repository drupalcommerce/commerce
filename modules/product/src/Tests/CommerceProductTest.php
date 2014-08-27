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
class CommerceProductTest extends CommerceProductTestBase {

  /**
   * Tests creating a product.
   */
  function testAddCommerceProduct() {
    $this->createEntity('commerce_product', array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'product'
    ));
  }

  /**
   * Tests creating a product with an existing SKU.
   */
  function testAddCommerceProductExistingSku() {
    $values = array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'product'
    );
    $this->createEntity('commerce_product', $values);

    $product_duplicate = entity_create("commerce_product", $values);

    $violations = $product_duplicate->sku->validate();
    $this->assertNotEqual($violations->count(), 0, 'Validation fails when creating a product with the same SKU.');
  }

  /**
   * Tests deleting a product.
   */
  function testDeleteProduct() {
    // Create a new product.
    $values = array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => "product"
    );
    $product = $this->createEntity('commerce_product', $values);
    $product_exists = (bool) CommerceProduct::load($product->id());
    $this->assertTrue($product_exists, 'The new product has been created in the database.');

    // Delete the product and verify deletion.
    $product->delete();

    $product_exists = (bool) CommerceProduct::load($product->id());
    $this->assertFalse($product_exists, 'The new product has been deleted from the database.');
  }
}