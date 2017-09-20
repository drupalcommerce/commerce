<?php

namespace Drupal\Tests\commerce_product\Functional;

/**
 * Tests the product variation field display injection.
 *
 * @group commerce
 */
class ProductVariationFieldInjectionTest extends ProductBrowserTestBase {

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

    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => $this->stores,
      'body' => ['value' => 'Testing product variation field injection!'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'INJECTION-CYAN',
          'attribute_color' => $attribute_values['cyan']->id(),
          'price' => [
            'number' => 999,
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'INJECTION-MAGENTA',
          'attribute_color' => $attribute_values['magenta']->id(),
          'price' => [
            'number' => 999,
            'currency_code' => 'USD',
          ],
        ]),
      ],
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
    $this->assertSession()->pageTextContains('Testing product variation field injection!');
    $this->assertSession()->pageTextContains('Price');
    $this->assertSession()->pageTextContains('$999.00');
    // We hide the SKU by default.
    $this->assertSession()->pageTextNotContains('INJECTION-CYAN');

    // Set a display for the color attribute.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $variation_view_display */
    $variation_view_display = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $variation_view_display->removeComponent('title');
    $variation_view_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    // Set the display for the SKU.
    $variation_view_display->setComponent('sku', [
      'label' => 'hidden',
      'type' => 'string',
    ]);

    $variation_view_display->save();

    // Have to call this save to get the cache to clear, we set the tags correctly in a hook,
    // but unless you trigger the submit it doesn't seem to clear. Something additional happens
    // on save that we're missing.
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/display');
    $this->submitForm([], 'Save');

    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->pageTextNotContains($this->product->label() . ' - Cyan');
    $this->assertSession()->pageTextContains('INJECTION-CYAN');
    $this->assertSession()->pageTextContains('$999.00');
  }

}
