<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the add to cart form.
 *
 * @group failing
 */
class AddToCartOptionalAttributeTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Tests add-to-cart form where variation have mutually exclusive attributes.
   *
   * @see https://www.drupal.org/node/2730643
   */
  public function testMutuallyExclusiveAttributeMatrixTwoByTwo() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    // All attribute-groups have an 'x' value that stand for 'empty'.
    $number_attributes = $this->createAttributeSet($variation_type, 'number', [
      'x' => 'x',
      'one' => 'one',
    ]);
    $greek_attributes = $this->createAttributeSet($variation_type, 'greek', [
      'x' => 'x',
      'alpha' => 'alpha',
    ]);

    $attribute_values_matrix = [
      ['one', 'x'],
      ['x', 'alpha'],
    ];

    // Generate variations from the attribute-matrix.
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'amount' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_number' => $number_attributes[$value[0]],
        'attribute_greek' => $greek_attributes[$value[1]],
      ]);
      $variations[] = $variation;
    }
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'OPTIONAL_ATTRIBUTES_TEST',
      'stores' => [$this->store],
      'variations' => $variations,
    ]);

    // Helper variables.
    $number_selector = 'purchased_entity[0][attributes][attribute_number]';
    $greek_selector = 'purchased_entity[0][attributes][attribute_greek]';


    // Initial state: ['one', 'x'].
    $this->drupalGet($product->toUrl());
    $this->assertAttributeSelected($number_selector, 'one');
    $this->assertAttributeSelected($greek_selector, 'x');
    // Expect that 'number' selector can be used.
    $this->assertAttributeExists($number_selector, $number_attributes['x']->id());
    // Expect that 'greek' selector cannot be used.
    $this->assertAttributeDoesNotExist($greek_selector, $greek_attributes['alpha']->id());


    // Use AJAX to change the number-attribute to 'x'.
    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption($number_selector, 'x');
    $this->waitForAjaxToFinish();
    // New state: ['x', 'alpha'].
    $this->assertAttributeSelected($number_selector, 'x');
    $this->assertAttributeSelected($greek_selector, 'alpha');
    // Expect that 'number' selector can be used.
    $this->assertAttributeExists($number_selector, $number_attributes['one']->id());
    // Expect that 'greek' selector cannot be used.
    $this->assertAttributeDoesNotExist($greek_selector, $greek_attributes['x']->id());

  }

  /**
   * Tests add-to-cart form where variation have mutually exclusive attributes.
   *
   * @see https://www.drupal.org/node/2730643
   */
  public function testMutuallyExclusiveAttributeMatrixTwoByTwobyTwo() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    // All attribute-groups have an 'x' value that stand for 'empty'.
    $number_attributes = $this->createAttributeSet($variation_type, 'number', [
      'x' => 'x',
      'one' => 'one',
    ]);
    $greek_attributes = $this->createAttributeSet($variation_type, 'greek', [
      'x' => 'x',
      'alpha' => 'alpha',
    ]);
    $city_attributes = $this->createAttributeSet($variation_type, 'city', [
      'x' => 'x',
      'milano' => 'milano',
    ]);

    $attribute_values_matrix = [
      ['one', 'x', 'x'],
      ['x', 'alpha', 'x'],
      ['x', 'x', 'milano'],
    ];

    // Generate variations from the attribute-matrix.
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'amount' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_number' => $number_attributes[$value[0]],
        'attribute_greek' => $greek_attributes[$value[1]],
        'attribute_city' => $city_attributes[$value[2]],
      ]);
      $variations[] = $variation;
    }
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'OPTIONAL_ATTRIBUTES_TEST',
      'stores' => [$this->store],
      'variations' => $variations,
    ]);

    // Helper variables.
    $number_selector = 'purchased_entity[0][attributes][attribute_number]';
    $greek_selector = 'purchased_entity[0][attributes][attribute_greek]';
    $city_selector = 'purchased_entity[0][attributes][attribute_city]';


    // Initial state: ['one', 'x', 'x'].
    $this->drupalGet($product->toUrl());
    $this->assertAttributeSelected($number_selector, 'one');
    $this->assertAttributeSelected($greek_selector, 'x');
    $this->assertAttributeSelected($city_selector, 'x');

    // Use AJAX to change the number-attribute to 'x'.
    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption($number_selector, 'x');
    $this->waitForAjaxToFinish();


    // Issues arise here...

    // New state: ['x', ?, ?].
    $this->assertAttributeSelected($number_selector, 'x');
    $this->assertAttributeSelected($greek_selector, 'x');
    $this->assertAttributeSelected($city_selector, 'x');
    // Expect all options can be chosen now?
    $this->assertAttributeExists($number_selector, $number_attributes['one']->id());
    $this->assertAttributeExists($greek_selector, $greek_attributes['alpha']->id());
    $this->assertAttributeExists($city_selector, $city_attributes['milano']->id());
  }

}
