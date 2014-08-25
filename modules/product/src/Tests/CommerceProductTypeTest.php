<?php

/**
 * @file
 * Definition of Drupal\commerce\Tests\CommerceProductTypeTest
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\CommerceProductType;

/**
 * Ensure the product type works correctly.
 *
 * @group commerce
 */
class CommerceProductTypeTest extends CommerceProductTestBase {

  /**
   * Tests if the default Product Type was created.
   */
  public function testDefaultProductType() {
    $product_types = CommerceProductType::loadMultiple();
    $this->assertTrue(isset($product_types['product']), 'Product Type Product is available');

    $commerce_product_type = CommerceProductType::load('product');
    $this->assertEqual($product_types['product'], $commerce_product_type, 'The correct Product Type is loaded');
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

    $type_exists = (bool) CommerceProductType::load($type->id());
    $this->assertTrue($type_exists, 'The new product type has been created in the database.');
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
    $commerce_product = $this->createEntity('commerce_product', $values);

    // Try to delete the product type.
    $type->delete();

    // Deleting the product type when its not being referenced by a product.
    $commerce_product->delete();
    $type_exists = (bool) CommerceProductType::load($type->id());
    $this->assertFalse($type_exists, 'The new product type has been deleted from the database.');

  }
}
