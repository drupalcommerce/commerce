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
   * Create 1 product with 2 product-variations without conflicting attributes.
   *
   * This test validates that 2 variations within one product can have
   * non-conflicting (unique) attributes.
   */
  public function testOneProductVariationWithoutAttributeConflict() {

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

    // Validate the product_variations. No variations must fail.
    $violations = $variation1->validate();
    $this->assertEqual(count($violations), 0);
    $violations = $variation2->validate();
    $this->assertEqual(count($violations), 0);

  }

  /**
   * Create 1 product with 2 product-variations with conflicting attributes.
   *
   * This test validates that 2 variations within one product cannot have
   * conflicting attributes.
   */
  public function testOneProductVariationWithAttributeConflict() {

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

    // Validate the product_variations. Both variations must fail.
    $violations = $variation1->validate();
    $this->assertEqual(count($violations), 1);
    $violations = $variation2->validate();
    $this->assertEqual(count($violations), 1);

  }

  /**
   * Create 2 products with 2 product-variations without conflicting attributes.
   *
   * This test validates a naive implementation of AttributeConstraintValidator
   * that doesn't take care of the product-id, but instead expects
   * non-conflicting attributes within all entities within a
   * product-variation-type.
   */
  public function testTwoProductVariationsWithoutAttributeConflict() {

    // Create attributes.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $attributes = $this->createAttributeSet($variation_type, 'color', [
      'green' => 'Green',
      'blue' => 'Blue',
    ]);


    // Product 1 test.
    // Create first product-variation with non-conflicting attributes.
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
      'title' => 'Conflicting variations',
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);
    $product->save();

    // Validate the product_variations. No variations must fail.
    $violations = $variation1->validate();
    $this->assertEqual(count($violations), 0);
    $violations = $variation2->validate();
    $this->assertEqual(count($violations), 0);


    // Product 2 test.
    // Create second product-variation with non-conflicting attributes.
    $variation1_alt = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1_alt',
      'attribute_color' => $attributes['green'],
    ]);
    $variation2_alt = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation2_alt',
      'attribute_color' => $attributes['blue'],
    ]);
    // Create 1 product with 2 product-variations.
    $product_alt = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Conflicting variations',
      'stores' => [$this->store],
      'variations' => [$variation1_alt, $variation2_alt],
    ]);
    $product_alt->save();

    // Validate the product_variations. No variations must fail.
    $violations = $variation1_alt->validate();
    $this->assertEqual(count($violations), 0);
    $violations = $variation2_alt->validate();
    $this->assertEqual(count($violations), 0);

  }

}
