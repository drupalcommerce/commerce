<?php

namespace Drupal\commerce_product\Tests;
use Drupal\commerce_product\ProductTestTrait;

/**
 * Tests the product variation field display injection.
 *
 * @group commerce
 */
class ProductVariationFieldInjectionTest extends ProductTestBase {

  use ProductTestTrait;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an attribute, so we can test it displays, too.
    $attribute = $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();
    \Drupal::service('commerce_product.attribute_field_manager')->createField($attribute, 'default');

    $attribute_values = [];
    foreach (['Cyan', 'Magenta', 'Yellow', 'Black'] as $color_attribute_value) {
      $attribute_values[strtolower($color_attribute_value)] = $this->createEntity('commerce_product_attribute_value', [
        'attribute' => $attribute->id(),
        'name' => $color_attribute_value,
      ]);
    }

    $variations = $this->createProductVariations('default', [
      ['sku' => 'INJECTION-CYAN', 'price' => 999, 'attribute_color' => $attribute_values['cyan']],
      ['sku' => 'INJECTION-MAGENTA', 'price' => 999, 'attribute_color' => $attribute_values['magenta']],
    ]);

    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => $this->stores,
      'body' => ['value' => 'Testing product variation field injection!'],
      'variations' => $variations,
    ]);
  }

  /**
   * Tests the fields from the attribute render.
   */
  public function testInjectedVariationDefault() {
    // Hide the variations field, so it does not render the variant titles.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $product_view_display */
    $product_view_display = commerce_get_entity_display('commerce_product', $this->product->bundle(), 'view');
    $product_view_display->removeComponent('variations');
    $product_view_display->save();

    $this->drupalGet($this->product->toUrl());
    $this->assertText('Testing product variation field injection!');
    $this->assertText('Price');
    $this->assertText('$999.00');
    $this->assertText('INJECTION-CYAN');
    $this->assertText($this->product->label() . ' - Cyan');

    // Set a display for the color attribute.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $variation_view_display */
    $variation_view_display = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $variation_view_display->removeComponent('title');
    $variation_view_display->removeComponent('sku');
    $variation_view_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    $variation_view_display->save();

    // Save the variation and reset its view caches. For some reason saving
    // the view display doesn't do this?
    $this->product->getDefaultVariation()->save();

    $this->drupalGet($this->product->toUrl());
    $this->assertNoText($this->product->label() . ' - Cyan');
    $this->assertNoText('INJECTION-CYAN');
    $this->assertText('$999.00');
  }

}
