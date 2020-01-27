<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the add to cart form.
 *
 * @group commerce
 */
class AddToCartFormTest extends CartBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * Test adding a product to the cart.
   */
  public function testProductAddToCartForm() {
    // Confirm that the initial add to cart submit works.
    $this->postAddToCart($this->variation->getProduct());
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variation, $order_items[0]);

    // Confirm that the second add to cart submit increments the quantity
    // of the first order item..
    $this->postAddToCart($this->variation->getProduct());
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    \Drupal::entityTypeManager()->getStorage('commerce_order_item')->resetCache();
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertNotEmpty(count($order_items) == 1, 'No additional order items were created');
    $this->assertOrderItemInOrder($this->variation, $order_items[0], 2);
  }

  /**
   * Test adding an unavailable product to the cart.
   */
  public function testProductAddToCartFormValidations() {
    $this->variation->setSku('TEST_SKU1234')->save();
    // Confirm that the initial add to cart submit works.
    $this->postAddToCart($this->variation->getProduct());
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertCount(0, $order_items);
    $this->assertSession()->pageTextContains(sprintf('%s is not available with a quantity of %s.', $this->variation->label(), 1
    ));
  }

  /**
   * Test assigning an anonymous cart to a logged in user.
   */
  public function testCartAssignment() {
    $this->drupalLogout();
    $this->postAddToCart($this->variation->getProduct());
    // Find the newly created anonymous cart.
    $query = \Drupal::entityQuery('commerce_order')
      ->condition('cart', TRUE)
      ->condition('uid', 0)
      ->accessCheck(FALSE);
    $result = $query->execute();
    $cart_id = reset($result);
    $cart = Order::load($cart_id);

    $this->assertEquals(0, $cart->getCustomerId());
    $this->assertNotEmpty($cart->hasItems());

    $this->drupalLogin($this->adminUser);
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    $cart = Order::load($cart->id());
    $this->assertEquals($this->adminUser->id(), $cart->getCustomerId());
  }

  /**
   * Test adding a product to the cart, via the variant's canonical link.
   */
  public function testVariationCanonicalLinkAddToCartForm() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attribute_values = $this->createAttributeSet($variation_type, 'color', [
      'cyan' => 'Cyan',
      'magenta' => 'Magenta',
    ]);

    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'not-canonical',
      'price' => [
        'number' => '5.00',
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['cyan'],
    ]);
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'canonical-test',
      'price' => [
        'number' => '9.99',
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['magenta'],
    ]);
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);

    $this->drupalGet($variation2->toUrl());
    $this->assertSession()->pageTextContains('$9.99');
    $this->submitForm([], 'Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertEquals($variation2->getSku(), $order_items[0]->getPurchasedEntity()->getSku());
  }

  /**
   * Tests ability to expose order item fields on the add to cart form.
   */
  public function testExposedOrderItemFields() {
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $order_item_form_display */
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('quantity', [
      'type' => 'commerce_quantity',
    ]);
    $order_item_form_display->save();

    // Confirm that the given quantity was accepted and saved.
    $this->postAddToCart($this->variation->getProduct(), [
      'quantity[0][value]' => 3,
    ]);
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variation, $order_items[0], 3);

    // Confirm that a zero quantity isn't accepted.
    $this->postAddToCart($this->variation->getProduct(), [
      'quantity[0][value]' => 0,
    ]);
    $this->assertSession()->pageTextContains('Quantity must be higher than or equal to 1.');
  }

  /**
   * Tests that the add to cart form renders an attribute entity.
   */
  public function testRenderedAttributeElement() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attribute_values = $this->createAttributeSet($variation_type, 'color', [
      'cyan' => 'Cyan',
      'magenta' => 'Magenta',
    ], TRUE);
    $color_attribute_values['cyan']->set('rendered_test', 'Cyan (Rendered)')->save();
    $color_attribute_values['cyan']->save();
    $color_attribute_values['magenta']->set('rendered_test', 'Magenta (Rendered)')->save();
    $color_attribute_values['magenta']->save();

    $color_attribute = ProductAttribute::load($color_attribute_values['cyan']->getAttributeId());

    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['cyan'],
    ]);
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['magenta'],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);

    $this->drupalGet($product->toUrl());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $color_attribute_values['cyan']->id());

    $color_attribute->set('elementType', 'commerce_product_rendered_attribute')->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->pageTextContains('Cyan (Rendered)');
    $this->assertSession()->pageTextContains('Magenta (Rendered)');
  }

  /**
   * Tests the behavior of optional product attributes.
   */
  public function testOptionalProductAttribute() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
    ]);
    // Make the color attribute optional.
    $color_field = FieldConfig::loadByName('commerce_product_variation', 'default', 'attribute_color');
    $color_field->setRequired(TRUE);
    $color_field->save();

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();
    // Update the first variation to have the attribute values.
    $this->variation->attribute_size = $size_attributes['small']->id();
    $this->variation->attribute_color = $color_attributes['red']->id();
    $this->variation->save();

    $attribute_values_matrix = [
      ['medium', 'red'],
      ['large', 'red'],
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
        'attribute_size' => $size_attributes[$value[0]]->id(),
        'attribute_color' => $color_attributes[$value[1]]->id(),
      ]);
      $variations[] = $variation;
      $product->variations->appendItem($variation);
    }
    $product->save();

    // The color element should be required because each variation has a color.
    $this->drupalGet($product->toUrl());
    $this->assertSession()->fieldExists('purchased_entity[0][attributes][attribute_size]');
    $this->assertSession()->elementExists('xpath', '//select[@id="edit-purchased-entity-0-attributes-attribute-color" and @required]');

    // Remove the color value from all variations.
    // The color element should now be hidden.
    foreach ($variations as $variation) {
      $variation->attribute_color = NULL;
      $this->variation->save();
    }
    $this->drupalGet($product->toUrl());
    $this->assertSession()->fieldExists('purchased_entity[0][attributes][attribute_size]');
    $this->assertSession()->fieldNotExists('purchased_entity[0][attributes][attribute_color]');
  }

}
