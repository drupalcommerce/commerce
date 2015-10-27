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
class ProductTest extends ProductTestBase {

  /**
   * Tests deleting a product.
   */
  function testDeleteProduct() {
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'variations' => [$variation],
    ]);

    // Delete the product and verify deletion.
    $product->delete();
    $productExists = (bool) Product::load($product->id());
    $variationExists = (bool) ProductVariation::load($variation->id());
    $this->assertFalse($productExists, 'The new product has been deleted from the database.');
    $this->assertFalse($variationExists, 'The matching product variation has been deleted from the database.');
  }

}
