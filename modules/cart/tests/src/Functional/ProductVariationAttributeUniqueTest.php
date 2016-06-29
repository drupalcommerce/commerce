<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests that attributes are unique between product variations.
 *
 * @group failing
 */
class ProductVariationAttributeUniqueTest extends CartBrowserTestBase {

  // Create 2 product-variations with same attribute.
  public function testProductVariationAttributeConflict() {

    // a. Create attribute type.
    // b. Create 1 attribute value for the created attribute type.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $this->createAttributeSet($variation_type, 'attribute_value', [
      'not_unique' => 'Not unique',
    ]);

    // c. Create product_variation type with attribute.
    // d. Create 1 product_variation with the attribute-value from b.
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1',
      'attribute_value' => 'not_unique',
    ]);
    // First must save.
    $this->assertTrue($variation1->id());

    // e. Create 2nd product_variation with the attribute-value from b.
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation2',
      'attribute_value' => 'not_unique',
    ]);
    // Second should fail saving.
    $this->assertFalse($variation2->id());


    // f. Query product-variations with attribute.
    $q = \Drupal::entityQuery('commerce_product_variation')
      ->condition('field_attribute_value', 'not_unique');
    $r = $q->execute();

    // Result count must be 1.
    $this->assertTrue(function ($r) {
      return count($r['commerce_product_variation']) === 1;
    });

    // Result must not be variation2.
    $this->assertFalse(function ($r) {
      return $r['commerce_product_variation'][0]['sku'] === 'variation2';
    });
  }

}
