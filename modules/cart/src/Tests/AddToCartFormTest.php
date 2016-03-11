<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Tests\AddToCartFormTest.
 */

namespace Drupal\commerce_cart\Tests;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Tests\OrderTestBase;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the add to cart form.
 *
 * @group commerce
 */
class AddToCartFormTest extends OrderTestBase {

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager for test cart operations.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer products',
      'access content',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store);
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
  }

  /**
   * Test adding a product to the cart.
   */
  public function testProductAddToCartForm() {
    // Get the existing product page and submit Add to cart form.
    $this->postAddToCart($this->variation->getProduct());

    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertEqual($line_item->getTitle(), $this->variation->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 1), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
  }

  /**
   * Tests adding a product to the cart when there are multiple variations.
   */
  public function testMultipleVariationsAddToCartForm() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $attribute_name = 'test_variation';
    $this->createAttributeField($variation_type, $attribute_name);

    // Reload the variation since we have a new field.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();

    // Update first variation to have the attribute's value.
    $attribute_value = $this->createAttributeValue($attribute_name, $this->randomMachineName());
    $this->variation->set($attribute_name, $attribute_value->id());
    $this->variation->save();

    $attribute_value = $this->createAttributeValue($attribute_name, $this->randomMachineName());
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      $attribute_name => $attribute_value->id(),
    ]);
    $variation2->save();

    $attribute_value = $this->createAttributeValue($attribute_name, $this->randomMachineName());
    $variation3 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      $attribute_name => $attribute_value->id(),
    ]);
    $variation3->save();

    $product->variations->appendItem($variation2);
    $product->variations->appendItem($variation3);
    $product->save();

    // Run the original add to cart test, ensure base variation is added.
    // Get the existing product page and submit Add to cart form.
    $this->postAddToCart($product);
    $this->assertEqual($variation3->{$attribute_name}->target_id, $attribute_value->id());
    $this->drupalPostAjaxForm(NULL, [
      'purchased_entity[0][attributes][test_variation]' => $variation3->{$attribute_name}->target_id,
    ], 'purchased_entity[0][attributes][test_variation]');
    $this->postAddToCart($product, [
      'purchased_entity[0][attributes][test_variation]' => $variation3->{$attribute_name}->target_id,
    ]);

    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertEqual($line_item->getTitle(), $this->variation->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 1), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
    $line_item = $line_items[1];
    $this->assertEqual($line_item->getTitle(), $variation3->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 1), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
  }

  /**
   * Tests adding a product to the cart when there are multiple variations.
   */
  public function testMultipleVariationsMultipleAttributes() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $this->createAttributeField($variation_type, 'test_size_attribute');
    /** @var \Drupal\taxonomy\TermInterface[] $size_attributes */
    $size_attributes = [
      'small' => $this->createAttributeValue('test_color_attribute', 'Small'),
      'medium' => $this->createAttributeValue('test_color_attribute', 'Medium'),
    ];

    $this->createAttributeField($variation_type, 'test_color_attribute');
    /** @var \Drupal\taxonomy\TermInterface[] $color_attributes */
    $color_attributes = [
      'red' => $this->createAttributeValue('test_color_attribute', 'Red'),
      'blue' => $this->createAttributeValue('test_color_attribute', 'Blue'),
    ];

    // Reload the variation since we have a new fields.
    $this->variation = ProductVariation::load($this->variation->id());

    // Get the product so we can append new variations
    $product = $this->variation->getProduct();

    /**
     * +--------------------+------------------+
     * | Variation          | Attributes       |
     * +--------------------+------------------+
     * | 1                  | Small, Red       |
     * | 2                  | Medium, Red      |
     * | 3                  | Medium, Blue     |
     * +--------------------+------------------+
     */

    // Update first variation to have the attribute's value.
    $this->variation->set('test_size_attribute', $size_attributes['small']->id());
    $this->variation->set('test_color_attribute', $color_attributes['red']->id());
    $this->variation->save();

    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'test_size_attribute' => $size_attributes['medium']->id(),
      'test_color_attribute' => $color_attributes['red']->id(),
    ]);
    $variation2->save();

    $variation3 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'test_size_attribute' => $size_attributes['medium']->id(),
      'test_color_attribute' => $color_attributes['blue']->id(),
    ]);
    $variation3->save();

    $product->variations->appendItem($variation2);
    $product->variations->appendItem($variation3);
    $product->save();

    $this->postAddToCart($this->variation->getProduct());
    // Trigger AJAX by changing size attribute
    $this->drupalPostAjaxForm(NULL, [
      'purchased_entity[0][attributes][test_size_attribute]' => $size_attributes['medium']->id(),
    ], 'purchased_entity[0][attributes][test_size_attribute]');
    // Trigger AJAX by changing color attribute
    $this->drupalPostAjaxForm(NULL, [
      'purchased_entity[0][attributes][test_color_attribute]' => $color_attributes['blue']->id(),
    ], 'purchased_entity[0][attributes][test_color_attribute]');

    // We can't assert an option doesn't exist using AssertContentTrait, since
    // our ID is dynamic. Version of assertNoOption using data-drupal-selector.
    // @see \Drupal\simpletest\AssertContentTrait::assertNoOption
    $selects = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]', [
      ':data_drupal_selector' => 'edit-purchased-entity-0-attributes-test-size-attribute',
    ]);
    $options = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]//option[@value=:option]', [
      ':data_drupal_selector' => 'edit-purchased-entity-0-attributes-test-size-attribute',
      ':option' => $size_attributes['small']->id(),
    ]);
    $this->assertTrue(isset($selects[0]) && !isset($options[0]), NULL, 'Browser');

    $selects = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector and @disabled]', [
      ':data_drupal_selector' => 'edit-purchased-entity-0-attributes-test-size-attribute',
    ]);
    $this->assertTrue(isset($selects[0]));

    // Since we do not have a Small, Blue. Should only see variation 3.
    $this->postAddToCart($product, [
      'purchased_entity[0][attributes][test_color_attribute]' => $color_attributes['blue']->id(),
      'purchased_entity[0][attributes][test_size_attribute]' => $size_attributes['medium']->id(),
    ]);

    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertEqual($line_item->getTitle(), $this->variation->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 1), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
    $line_item = $line_items[1];
    $this->assertEqual($line_item->getTitle(), $variation3->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 1), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
  }

  /**
   * Tests ability to expose line item fields on the add to cart form.
   */
  public function testExposedLineItemFields() {
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $line_item_form_display */
    $line_item_form_display = EntityFormDisplay::load('commerce_line_item.product_variation.add_to_cart');
    $line_item_form_display->setComponent('quantity', [
      'type' => 'number',
    ]);
    $line_item_form_display->save();

    // Get the existing product page and submit Add to cart form.
    $this->postAddToCart($this->variation->getProduct(), [
      'quantity[0][value]' => 3,
    ]);
    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertEqual($line_item->getTitle(), $this->variation->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == 3), t('The product @product has been added to cart.', ['@product' => $line_item->getTitle()]));
  }

  /**
   * Helper method to create an attribute field on a variation type.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   * @param string $field_name
   *   The field name.
   */
  protected function createAttributeField(ProductVariationTypeInterface $variation_type, $field_name) {
    $attribute = ProductAttribute::create([
      'id' => $field_name,
      'label' => $field_name,
    ]);
    $attribute->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'commerce_product_variation',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'commerce_product_attribute_value',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $variation_type->id(),
      'label' => $field_name,
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [$attribute->id() => $attribute->id()],
          'auto_create' => TRUE,
        ],
      ],
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->save();
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute id.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   */
  protected function createAttributeValue($attribute, $name) {
    $attribute_value = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
  }

  /**
   * Posts the add to cart form for a product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   * @param array $edit
   *
   * @throws \Exception
   */
  protected function postAddToCart(ProductInterface $product, array $edit = []) {
    $this->drupalGet('product/' . $product->id());
    $this->assertField('edit-submit', t('Add to cart button exists.'));
    $this->drupalPostForm(NULL, $edit, t('Add to cart'));
  }

}
