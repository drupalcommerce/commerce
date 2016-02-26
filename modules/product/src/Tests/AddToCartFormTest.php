<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\AddToCartFormTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Tests\OrderTestBase;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;

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
    $attribute = $this->createAttributeOption($attribute_name, $this->randomMachineName());
    $this->variation->set($attribute_name, $attribute->id());
    $this->variation->save();

    $attribute = $this->createAttributeOption($attribute_name, $this->randomMachineName());
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      $attribute_name => $attribute->id(),
    ]);
    $variation2->save();

    $attribute = $this->createAttributeOption($attribute_name, $this->randomMachineName());
    $variation3 = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      $attribute_name => $attribute->id(),
    ]);
    $variation3->save();

    $product->variations->appendItem($variation2);
    $product->variations->appendItem($variation3);
    $product->save();

    // Run the original add to cart test, ensure base variation is added.
    // Get the existing product page and submit Add to cart form.
    $this->postAddToCart($product);
    $this->assertEqual($variation3->{$attribute_name}->target_id, $attribute->id());
    $this->drupalPostAjaxForm(NULL, [
      'attributes[test_variation]' => $variation3->{$attribute_name}->target_id,
    ], 'attributes[test_variation]');
    $this->assertAttributeExists('edit-attributes-test-variation', $variation3->{$attribute_name}->target_id);
    $this->postAddToCart($product, [
      'attributes[test_variation]' => $variation3->{$attribute_name}->target_id,
    ]);

    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertLineItemInOrder($this->variation, $line_item);
    $line_item = $line_items[1];
    $this->assertLineItemInOrder($variation3, $line_item);
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
      'small' => $this->createAttributeOption('test_color_attribute', 'Small'),
      'medium' => $this->createAttributeOption('test_color_attribute', 'Medium'),
    ];

    $this->createAttributeField($variation_type, 'test_color_attribute');
    /** @var \Drupal\taxonomy\TermInterface[] $color_attributes */
    $color_attributes = [
      'red' => $this->createAttributeOption('test_color_attribute', 'Red'),
      'blue' => $this->createAttributeOption('test_color_attribute', 'Blue'),
    ];

    // Reload the variation since we have a new fields.
    $this->variation = ProductVariation::load($this->variation->id());

    // Get the product so we can append new variations.
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

    // Drill down to "medium" and "blue" variation.
    $this->drupalPostAjaxForm(NULL, [
      'attributes[test_size_attribute]' => $size_attributes['medium']->id(),
    ], 'attributes[test_size_attribute]');
    $this->assertAttributeExists('edit-attributes-test-color-attribute', $color_attributes['red']->id());
    $this->assertAttributeExists('edit-attributes-test-color-attribute', $color_attributes['blue']->id());

    $this->drupalPostAjaxForm(NULL, [
      'attributes[test_color_attribute]' => $color_attributes['blue']->id(),
    ], 'attributes[test_color_attribute]');
    $this->assertAttributeExists('edit-attributes-test-color-attribute', $color_attributes['red']->id());
    $this->assertAttributeExists('edit-attributes-test-color-attribute', $color_attributes['blue']->id());
    $this->assertAttributeDoesNotExist('edit-attributes-test-size-attribute', $size_attributes['small']->id());

    $selects = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector and @disabled]', [
      ':data_drupal_selector' => 'edit-attributes-test-size-attribute',
    ]);
    $this->assertTrue(isset($selects[0]));

    // Since we do not have a Small, Blue. Should only see variation 3.
    $this->postAddToCart($product, [
      'attributes[test_color_attribute]' => $color_attributes['blue']->id(),
      'attributes[test_size_attribute]' => $size_attributes['medium']->id(),
    ]);

    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $line_item = $line_items[0];
    $this->assertLineItemInOrder($this->variation, $line_item);
    $line_item = $line_items[1];
    $this->assertLineItemInOrder($variation3, $line_item);
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
    $attribute_vocabulary = Vocabulary::create([
      'name' => $field_name,
      'vid' => $field_name,
    ]);
    $attribute_vocabulary->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'commerce_product_variation',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $variation_type->id(),
      'label' => $field_name,
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [$attribute_vocabulary->id() => $attribute_vocabulary->id()],
          'auto_create' => TRUE,
        ],
      ],
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->setThirdPartySetting('commerce_product', 'attribute_widget_title', '');
    $field->setThirdPartySetting('commerce_product', 'attribute_widget', 'select');
    $field->setThirdPartySetting('commerce_product', 'attribute_field', TRUE);
    $field->save();
  }

  /**
   * Creates an attribute.
   *
   * @param string $vocabulary
   *   The vocabulary ID.
   * @param string $name
   *   The attribute name.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created attribute.
   */
  protected function createAttributeOption($vocabulary, $name) {
    $attribute = $this->createEntity('taxonomy_term', [
      'vid' => $vocabulary,
      'name' => $name,
    ]);
    $attribute->save();

    return $attribute;
  }

  /**
   * Posts the add to cart form for a product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param array $edit
   *   The values to post.
   */
  protected function postAddToCart(ProductInterface $product, array $edit = []) {
    $this->drupalGet('product/' . $product->id());
    $this->assertField('edit-submit', t('Add to cart button exists.'));
    $this->drupalPostForm(NULL, $edit, t('Add to cart'));
  }

  /**
   * Asserts that an attribute option does exist.
   *
   * @param string $selector
   *   The element selector.
   * @param string $option
   *   The option.
   */
  protected function assertAttributeExists($selector, $option) {
    $options = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]//option[@value=:option]', [
      ':data_drupal_selector' => $selector,
      ':option' => $option,
    ]);
    $this->assertFalse(empty($options), 'The attribute is not available');
  }

  /**
   * Asserts that an attribute option does not exist.
   *
   * @param string $selector
   *   The element selector.
   * @param string $option
   *   The option.
   */
  protected function assertAttributeDoesNotExist($selector, $option) {
    $options = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]//option[@value=:option]', [
      ':data_drupal_selector' => $selector,
      ':option' => $option,
    ]);
    $this->assertTrue(empty($options), 'The attribute is not available');
  }

  /**
   * Assert the line item in the order is correct.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The purchased product variation.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param int $quantity
   *   The quantity.
   */
  protected function assertLineItemInOrder(ProductVariationInterface $variation, LineItemInterface $line_item, $quantity = 1) {
    $this->assertEqual($line_item->getTitle(), $variation->getLineItemTitle());
    $this->assertTrue(($line_item->getQuantity() == $quantity), t('The product @product has been added to cart with quantity of @quantity.', [
      '@product' => $line_item->getTitle(),
      '@quantity' => $line_item->getQuantity(),
    ]));
  }

}
