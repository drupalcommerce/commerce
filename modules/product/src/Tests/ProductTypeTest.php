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
class ProductTypeTest extends CommerceProductTestBase {

  /**
   * Tests if the default Product Type was created.
   */
  public function testDefaultProductType() {
    $productTypes = ProductType::loadMultiple();
    $this->assertTrue(isset($productTypes['product']), 'Product Type Product is available');

    $productType = ProductType::load('product');
    $this->assertEqual($productTypes['product'], $productType, 'The correct Product Type is loaded');
  }

  /**
   * Tests creating a Product Type programaticaly and through the create form.
   */
  public function testCreateProductType() {
    $title = strtolower($this->randomMachineName(8));

    // Create a product type programmaticaly.
    $type = $this->createEntity('commerce_product_type', array(
        'id' => $title,
        'label' => $title,
      )
    );

    $typeExists = (bool) ProductType::load($type->id());
    $this->assertTrue($typeExists, 'The new product type has been created in the database.');
  }

  /**
   * Tests deleting a Product Type through the form.
   */
  public function testDeleteProductType() {
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
      'type' => "product"
    );
    $product = $this->createEntity('commerce_product', $values);

    // Try to delete the product type.
    $type->delete();

    // Deleting the product type when its not being referenced by a product.
    $product->delete();
    $typeExists = (bool) ProductType::load($type->id());
    $this->assertFalse($typeExists, 'The new product type has been deleted from the database.');

  }
}
