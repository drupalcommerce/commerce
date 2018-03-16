<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the add to cart form.
 *
 * @group commerce
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
      'one' => 'one',
      'two' => 'two',
    ]);
    $greek_attributes = $this->createAttributeSet($variation_type, 'greek', [
      'alpha' => 'alpha',
      'omega' => 'omega',
    ]);

    $attribute_values_matrix = [
      ['one', 'omega'],
      ['two', 'alpha'],
    ];

    // Generate variations from the attribute-matrix.
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
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
    $this->assertAttributeSelected($greek_selector, 'omega');
    // Expect that 'number' selector can be used.
    $this->assertAttributeExists($number_selector, $number_attributes['two']->id());
    // Expect that 'greek' selector cannot be used.
    $this->assertAttributeDoesNotExist($greek_selector, $greek_attributes['alpha']->id());


    // Use AJAX to change the number-attribute to 'x'.
    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption($number_selector, 'two');
    $this->waitForAjaxToFinish();
    // New state: ['x', 'alpha'].
    $this->assertAttributeSelected($number_selector, 'two');
    $this->assertAttributeSelected($greek_selector, 'alpha');
    // Expect that 'number' selector can be used.
    $this->assertAttributeExists($number_selector, $number_attributes['one']->id());
    // Expect that 'greek' selector cannot be used.
    $this->assertAttributeDoesNotExist($greek_selector, $greek_attributes['omega']->id());

  }

  /**
   * Tests add-to-cart form where variation have mutually exclusive attributes.
   *
   * @group debug
   *
   * @see https://www.drupal.org/node/2730643
   */
  public function testMutuallyExclusiveAttributeMatrixTwoByTwobyTwo() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    // All attribute-groups have an 'x' value that stand for 'empty'.
    $number_attributes = $this->createAttributeSet($variation_type, 'number', [
      'one' => 'one',
      'two' => 'two',
    ]);
    $greek_attributes = $this->createAttributeSet($variation_type, 'greek', [
      'alpha' => 'alpha',
      'omega' => 'omega',
    ]);
    $city_attributes = $this->createAttributeSet($variation_type, 'city', [
      'milano' => 'milano',
      'pancevo' => 'pancevo',
    ]);

    $attribute_values_matrix = [
      ['one', 'omega', 'pancevo'],
      ['two', 'alpha', 'pancevo'],
      ['two', 'omega', 'milano'],
    ];

    // Generate variations from the attribute-matrix.
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
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


    // Initial state: ['one', 'omega', 'pancevo'].
    $this->drupalGet($product->toUrl());
    $this->assertAttributeSelected($number_selector, 'one');
    $this->assertAttributeSelected($greek_selector, 'omega');
    $this->assertAttributeSelected($city_selector, 'pancevo');

    // Use AJAX to change the number-attribute to 'two'.
    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption($number_selector, 'two');
    $this->waitForAjaxToFinish();
    $this->saveHtmlOutput();

    // New state: ['two', 'alpha', 'pancevo'].
    // The top level attribute was adjusted, so the options are reset.
    $this->assertAttributeSelected($number_selector, 'two');
    $this->assertAttributeSelected($greek_selector, 'alpha');
    $this->assertAttributeSelected($city_selector, 'pancevo');

    $this->assertAttributeExists($number_selector, $number_attributes['one']->id());
    $this->assertAttributeExists($greek_selector, $greek_attributes['omega']->id());
    $this->assertAttributeExists($city_selector, $city_attributes['pancevo']->id());

    // Use AJAX to change the number-attribute to 'two'.
    $this->drupalGet($product->toUrl());
    $this->getSession()->getPage()->selectFieldOption($greek_selector, 'omega');
    $this->waitForAjaxToFinish();
    $this->saveHtmlOutput();

    // New state: ['one', 'omega', 'pancevo'].
    // The top level attribute was adjusted, so the options are reset.
    $this->assertAttributeSelected($number_selector, 'one');
    $this->assertAttributeSelected($greek_selector, 'omega');
    $this->assertAttributeSelected($city_selector, 'pancevo');

    $this->assertAttributeExists($number_selector, $number_attributes['two']->id());
    $this->assertAttributeDoesNotExist($greek_selector, $greek_attributes['alpha']->id());
    // We should not be able to change the city.
    // There is one variation with "one" and "omega", which means there is only
    // one city option.
    $this->assertAttributeDoesNotExist($city_selector, $city_attributes['milano']->id());
  }

}
