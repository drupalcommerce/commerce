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
class CommerceProductTest extends CommerceProductTestBase {

  /**
   * Tests creating a commerce product.
   */
  function testAddCommerceProduct() {
    $this->createEntity('commerce_product', array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'type'
    ));
  }

  /**
   * Tests creating a commerce product with an existing SKU.
   */
  function testAddCommerceProductExistingSku() {
    $values = array(
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
      'type' => 'type'
    );
    $this->createEntity('commerce_product', $values);

    $commerce_product_duplicate = entity_create("commerce_product", $values);

    $violations = $commerce_product_duplicate->sku->validate();
    $this->assertNotEqual($violations->count(), 0, 'Validation fails when creating a product with the same SKU.');
  }
}