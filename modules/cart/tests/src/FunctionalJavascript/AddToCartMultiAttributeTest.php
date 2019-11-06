<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests the add to cart form.
 *
 * @group commerce
 */
class AddToCartMultiAttributeTest extends CartWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $variation_view_display */
    $variation_view_display = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    // Show the SKU by default.
    $variation_view_display->setComponent('sku', [
      'label' => 'hidden',
      'type' => 'string',
    ]);
    $variation_view_display->save();
  }

  /**
   * Tests adding a product to the cart when there are multiple variations.
   */
  public function testMultipleVariations() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();

    // Update first variation to have the attribute's value.
    $this->variation->attribute_color = $color_attributes['red']->id();
    $this->variation->attribute_size = $size_attributes['small']->id();
    $this->variation->save();

    // The matrix is intentionally uneven, blue / large is missing.
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];
    $variations = [
      $this->variation,
    ];
    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_color' => $color_attributes[$value[0]],
        'attribute_size' => $size_attributes[$value[1]],
      ]);
      $variations[] = $variation;
      $product->variations->appendItem($variation);
    }
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Small');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $color_attributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $size_attributes['medium']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $size_attributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->drupalGet($product->toUrl());
    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $color_attributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $size_attributes['small']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $size_attributes['large']->id());

    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'Blue');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $color_attributes['red']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $size_attributes['small']->id());
    $this->assertAttributeDoesNotExist('purchased_entity[0][attributes][attribute_size]', $size_attributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($variations[0], $order_items[0]);
    $this->assertOrderItemInOrder($variations[5], $order_items[1]);
  }

  /**
   * Tests that the cart refreshes rendered variation fields.
   */
  public function testRenderedVariationFields() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attribute_values = $this->createAttributeSet($variation_type, 'color', [
      'cyan' => 'Cyan',
      'magenta' => 'Magenta',
    ], TRUE);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation1 */
    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'RENDERED_VARIATION_TEST_CYAN',
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['cyan'],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation2 */
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'RENDERED_VARIATION_TEST_MAGENTA',
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['magenta'],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'RENDERED_VARIATION_TEST',
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);

    $this->drupalGet($product->toUrl());
    $this->assertSession()->pageTextContains($variation1->getSku());

    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'Magenta');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($variation2->getSku());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Magenta');

    // Load variation2 directly via the url (?v=).
    $this->drupalGet($variation2->toUrl());
    $this->assertSession()->pageTextContains($variation2->getSku());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Magenta');
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'Cyan');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($variation1->getSku());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Cyan');
  }

}
