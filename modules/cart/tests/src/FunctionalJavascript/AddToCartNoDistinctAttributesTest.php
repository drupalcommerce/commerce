<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the add-to-cart.
 *
 * @covers Variations that are not distinguishable by their attributes.
 *
 * @group failing
 */
class AddToCartNoDistinctAttributesTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Test one variation without attributes.
   */
  public function testSingleVariationWithoutAttributes() {

    // Create a product with 1 variation and no attributes.
    $variation0 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation0',
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation0],
    ]);
    $product->save();


    // Go to the add-to-cart form.
    $this->drupalGet($product->toUrl());


    // Validate information from variation0 is on the page.
    $this->assertSession()->pageTextContains('variation0');
    $this->assertSession()->pageTextContains('999');
    // Add the variation0 to the cart.
    $this->getSession()->getPage()->pressButton('Add to cart');


    // Validate that variation0 is in the cart.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($variation0, $line_items[0]);
  }

  /**
   * Test two variation without attributes.
   *
   * @todo: fix bug with variation selector.
   */
  public function testMultipleVariationsWithoutAttributes() {

    // Create a product with 2 variations and no attributes.
    $variation0 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation0',
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
    ]);
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1',
      'price' => [
        'amount' => 100,
        'currency_code' => 'USD',
      ],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation0, $variation1],
    ]);
    $product->save();


    // Go to the add-to-cart form.
    $this->drupalGet($product->toUrl());


    // Validate information from variation0 is on the page.
    $this->assertSession()->pageTextContains('variation0');
    $this->assertSession()->pageTextContains('999');
    // Add the variation0 to the cart.
    $this->getSession()->getPage()->pressButton('Add to cart');

    // @todo: cannot select variation1.
    // Validate information from variation1 is on the page.
    $this->assertSession()->pageTextContains('variation1');
    $this->assertSession()->pageTextContains('100');
    // Add the variation1 to the cart.


    // Validate that variation0 and variation1 are in the cart.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($variation0, $line_items[0]);
    $this->assertLineItemInOrder($variation1, $line_items[1]);
  }

  /**
   * Test one variation with one attribute.
   */
  public function testSingleVariationWithEqualAttributes() {

    // Create a product with 1 variation and 1 equal attribute.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $this->createAttributeSet($variation_type, 'attribute_equal', [
      'equal' => 'equal',
    ]);

    // Create a product variation.
    $variation0 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation0',
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_equal' => 'equal',
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation0],
    ]);
    $product->save();


    // Go to the add-to-cart form.
    $this->drupalGet($product->toUrl());


    // Validate information from variation0 is on the page.
    $this->assertSession()->pageTextContains('variation0');
    $this->assertSession()->pageTextContains('999');
    // Add the variation0 to the cart.
    $this->getSession()->getPage()->pressButton('Add to cart');


    // Validate that variation0 is in the cart.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($variation0, $line_items[0]);
  }

  /**
   * Test two variations with the same attribute.
   *
   * @todo: fix bug with variation selector.
   */
  public function testMultipleVariationsWithEqualAttributes() {

    // Create a product with 2 variations and 1 equal attribute.
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $this->createAttributeSet($variation_type, 'attribute_equal', [
      'equal' => 'equal',
    ]);

    $variation0 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation0',
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_equal' => 'equal',
    ]);
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'variation1',
      'price' => [
        'amount' => 100,
        'currency_code' => 'USD',
      ],
      'attribute_equal' => 'equal',
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation0, $variation1],
    ]);
    $product->save();

    // Go to the add-to-cart form.
    $this->drupalGet($product->toUrl());


    // Validate information from variation0 is on the page.
    $this->assertSession()->pageTextContains('variation0');
    $this->assertSession()->pageTextContains('999');
    // Add the variation0 to the cart.
    $this->getSession()->getPage()->pressButton('Add to cart');

    // @todo: cannot select variation1.
    // Validate information from variation1 is on the page.
    $this->assertSession()->pageTextContains('variation1');
    $this->assertSession()->pageTextContains('100');
    // Add the variation1 to the cart.


    // Validate that variation0 and variation1 are in the cart.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($variation0, $line_items[0]);
    $this->assertLineItemInOrder($variation1, $line_items[1]);
  }

}
