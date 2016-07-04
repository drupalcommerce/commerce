<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests that attributes are unique between product variations.
 *
 * @group failing
 */
class ProductVariationAttributeUniqueTest extends CartBrowserTestBase {

  /**
   * Create 1 product with 2 product-variations with non-conflicting attributes.
   */
  public function testProductVariationAttributeNoConflict() {

    // Create attributes.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $attributes = $this->createAttributeSet($variation_type, 'color', [
      'green' => 'Green',
      'blue' => 'Blue',
    ]);

    // Create 2 product-variations with non-conflicting attributes.
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1',
      'attribute_color' => $attributes['green'],
    ]);
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation2',
      'attribute_color' => $attributes['blue'],
    ]);
    // Create 1 product with 2 product-variations.
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Not conflicting variations',
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);
    $product->save();

    // Validate the product_variations.
    $violations = $variation1->validate();
    $this->assertEqual(count($violations), 0);
    $violations = $variation2->validate();
    $this->assertEqual(count($violations), 0);

  }

  /**
   * Create 1 product with 2 product-variations with conflicting attributes.
   */
  public function testProductVariationAttributeWithConflict() {

    // Create attributes.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $attributes = $this->createAttributeSet($variation_type, 'color', [
      'green' => 'Green',
      'blue' => 'Blue',
    ]);

    // Create 2 product-variations with non-conflicting attributes.
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1',
      'attribute_color' => $attributes['green'],
    ]);
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation2',
      'attribute_color' => $attributes['green'], // CONFLICT.
    ]);
    // Create 1 product with 2 product-variations.
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Conflicting variations',
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);
    $product->save();

    // Validate the product_variations.
    $violations = $variation1->validate();
    $this->assertEqual(count($violations), 1);
    $violations = $variation2->validate();
    $this->assertEqual(count($violations), 1);

  }

}
