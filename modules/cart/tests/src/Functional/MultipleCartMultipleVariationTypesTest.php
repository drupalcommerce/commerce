<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests multiple cart page with different variation types.
 *
 * @see https://www.drupal.org/node/2893182
 *
 * @note This Functional version kept for quick testing.
 *
 * @group commerce
 */
class MultipleCartMultipleVariationTypesTest extends CartBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart_test',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $colorAttributes = [];

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $sizeAttributes = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Unpublish parent test product.
    $this->variation->getProduct()->setUnpublished();
    $this->variation->getProduct()->save();

    // Create three variation types.
    $this->createProductAndVariationType('color_sizes', 'Colors and Sizes');
    $this->createProductAndVariationType('colors', 'Colors');
    $this->createProductAndVariationType('sizes', 'Sizes');

    // Create the attributes.
    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color_attribute->save();
    $this->attributeFieldManager->createField($color_attribute, 'color_sizes');
    $this->attributeFieldManager->createField($color_attribute, 'colors');

    $options = ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue'];
    foreach ($options as $key => $value) {
      $this->colorAttributes[$key] = $this->createAttributeValue($color_attribute->id(), $value);
    }

    $size_attribute = ProductAttribute::create([
      'id' => 'size',
      'label' => 'Size',
    ]);
    $size_attribute->save();
    $this->attributeFieldManager->createField($size_attribute, 'color_sizes');
    $this->attributeFieldManager->createField($size_attribute, 'sizes');

    $options = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
    foreach ($options as $key => $value) {
      $this->sizeAttributes[$key] = $this->createAttributeValue($size_attribute->id(), $value);
    }

    // Create products.
    $product_matrix = [
      'My Colors and Sizes FIRST' => [
        'type' => 'color_sizes',
        'variations' => [
          ['attribute_color' => $this->colorAttributes['red']->id(), 'attribute_size' => $this->sizeAttributes['small']->id()],
          ['attribute_color' => $this->colorAttributes['red']->id(), 'attribute_size' => $this->sizeAttributes['medium']->id()],
          ['attribute_color' => $this->colorAttributes['red']->id(), 'attribute_size' => $this->sizeAttributes['large']->id()],
          ['attribute_color' => $this->colorAttributes['green']->id(), 'attribute_size' => $this->sizeAttributes['small']->id()],
          ['attribute_color' => $this->colorAttributes['green']->id(), 'attribute_size' => $this->sizeAttributes['medium']->id()],
          ['attribute_color' => $this->colorAttributes['green']->id(), 'attribute_size' => $this->sizeAttributes['large']->id()],
          ['attribute_color' => $this->colorAttributes['blue']->id(), 'attribute_size' => $this->sizeAttributes['small']->id()],
          ['attribute_color' => $this->colorAttributes['blue']->id(), 'attribute_size' => $this->sizeAttributes['medium']->id()],
          ['attribute_color' => $this->colorAttributes['blue']->id(), 'attribute_size' => $this->sizeAttributes['large']->id()],
        ],
      ],
      'My Colors FIRST' => [
        'type' => 'colors',
        'variations' => [
          ['attribute_color' => $this->colorAttributes['red']->id()],
          ['attribute_color' => $this->colorAttributes['green']->id()],
          ['attribute_color' => $this->colorAttributes['blue']->id()],
        ],
      ],
      'My Sizes FIRST' => [
        'type' => 'sizes',
        'variations' => [
          ['attribute_size' => $this->sizeAttributes['small']->id()],
          ['attribute_size' => $this->sizeAttributes['medium']->id()],
          ['attribute_size' => $this->sizeAttributes['large']->id()],
        ],
      ],
    ];
    foreach ($product_matrix as $product_title => $product_data) {
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $this->createEntity('commerce_product', [
        'type' => $product_data['type'],
        'title' => $product_title,
        'stores' => [$this->store],
      ]);
      foreach ($product_data['variations'] as $variation_data) {
        $variation_data += [
          'type' => $product_data['type'],
          'sku' => 'sku-' . $this->randomMachineName(),
          'price' => [
            'number' => '10',
            'currency_code' => 'USD',
          ],
        ];
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
        $variation = $this->createEntity('commerce_product_variation', $variation_data);
        $product->addVariation($variation);
      }
      $product->save();
    }
  }

  /**
   * Tests that add to cart does not throw an exception.
   */
  public function testAddToCart() {
    $this->drupalGet('/test-multiple-cart-forms');
    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');

    // Due to sorting by title, we know `My Colors FIRST` is the second form.
    $forms[1]->selectFieldOption('Color', 'Blue');
    $forms[1]->pressButton('Add to cart');
    $this->assertSession()->pageTextContains('My Colors FIRST - Blue added to your cart.');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $order_items[0]->getPurchasedEntity();
    $this->assertEquals($this->colorAttributes['blue']->id(), $variation->getAttributeValueId('attribute_color'));

    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $forms[0]->selectFieldOption('Color', 'Green');
    $forms[0]->selectFieldOption('Size', 'Medium');
    $forms[0]->pressButton('Add to cart');
    $this->assertSession()->pageTextContains('My Colors and Sizes FIRST - Green, Medium added to your cart.');

    $this->container->get('entity_type.manager')->getStorage('commerce_order')->resetCache([$this->cart->id()]);
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $order_items[1]->getPurchasedEntity();
    $this->assertEquals($this->colorAttributes['green']->id(), $variation->getAttributeValueId('attribute_color'));
    $this->assertEquals($this->sizeAttributes['medium']->id(), $variation->getAttributeValueId('attribute_size'));
  }

  /**
   * Creates a product and product variation type.
   *
   * @param string $id
   *   The ID.
   * @param string $label
   *   The label.
   */
  protected function createProductAndVariationType($id, $label) {
    $variation_type = ProductVariationType::create([
      'id' => $id,
      'label' => $label,
      'orderItemType' => 'default',
      'generateTitle' => TRUE,
    ]);
    $variation_type->save();

    $product_type = ProductType::create([
      'id' => $id,
      'label' => $label,
      'variationType' => $variation_type->id(),
    ]);
    $product_type->save();
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);
  }

}
