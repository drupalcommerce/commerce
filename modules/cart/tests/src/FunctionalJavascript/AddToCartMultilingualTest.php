<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the add to cart form for multilingual.
 *
 * @group commerce
 */
class AddToCartMultilingualTest extends CartWebDriverTestBase {

  /**
   * The variations to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation[]
   */
  protected $variations;

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\Product
   */
  protected $product;

  /**
   * The color attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $colorAttributes;

  /**
   * The size attributes to test with.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $sizeAttributes;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Tests\content_translation\Functional\ContentTranslationTestBase
   */
  public function setUp() {
    parent::setUp();

    $this->setupMultilingual();

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);

    foreach ($color_attributes as $key => $color_attribute) {
      $color_attribute->addTranslation('fr', [
        'name' => 'FR ' . $color_attribute->label(),
      ]);
      $color_attribute->save();
    }
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    foreach ($size_attributes as $key => $size_attribute) {
      $size_attribute->addTranslation('fr', [
        'name' => 'FR ' . $size_attribute->label(),
      ]);
      $size_attribute->save();
    }

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());

    // Translate the product's title.
    $product = $this->variation->getProduct();
    $product->setTitle('My Super Product');
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->save();

    // Update first variation to have the attribute's value.
    $this->variation->get('attribute_color')->setValue($color_attributes['red']);
    $this->variation->get('attribute_size')->setValue($size_attributes['small']);
    $this->variation->save();

    // The matrix is intentionally uneven, blue / large is missing.
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];

    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
      ]);
      $variation->get('attribute_color')->setValue($color_attributes[$value[0]]);
      $variation->get('attribute_size')->setValue($size_attributes[$value[1]]);
      $variation->save();
      $product->addVariation($variation);
    }

    $product->save();
    $this->product = Product::load($product->id());

    // Create a translation for each variation on the product.
    foreach ($this->product->getVariations() as $variation) {
      $variation->addTranslation('fr')->save();
    }

    $this->variations = $product->getVariations();
    $this->colorAttributes = $color_attributes;
    $this->sizeAttributes = $size_attributes;
  }

  /**
   * Sets up the multilingual items.
   */
  protected function setupMultilingual() {
    // Add a new language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Enable translation for the product and ensure the change is picked up.
    $this->container->get('content_translation.manager')->setEnabled('commerce_product', $this->variation->bundle(), TRUE);
    $this->container->get('content_translation.manager')->setEnabled('commerce_product_variation', $this->variation->bundle(), TRUE);
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $this->container->get('router.builder')->rebuild();

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Tests that the attribute widget uses translated items.
   */
  public function testProductVariationAttributesWidget() {
    $this->drupalGet($this->product->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Small');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['medium']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->rebuildContainer();

    $this->drupalGet($this->product->getTranslation('fr')->toUrl());
    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'FR Red');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['blue']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['small']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());

    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][attributes][attribute_color]', 'FR Blue');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'FR Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $this->colorAttributes['red']->id());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['small']->id());
    $this->assertAttributeDoesNotExist('purchased_entity[0][attributes][attribute_size]', $this->sizeAttributes['large']->id());
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variations[0]->getTranslation('fr'), $order_items[0]);
    $this->assertOrderItemInOrder($this->variations[5]->getTranslation('fr'), $order_items[1]);
  }

  /**
   * Tests the attribute widget default values with a variation url (?v=).
   */
  public function testProductVariationAttributesWidgetFromUrl() {
    $variation = $this->variations[5];
    $this->drupalGet($variation->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->rebuildContainer();

    $variation = $variation->getTranslation('fr');
    $this->drupalGet($variation->toUrl());
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_color]', 'FR Blue');
    $this->assertAttributeSelected('purchased_entity[0][attributes][attribute_size]', 'FR Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');
  }

  /**
   * Tests the title widget has translated variation title.
   */
  public function testProductVariationTitleWidget() {
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title',
    ]);
    $order_item_form_display->save();

    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->selectExists('purchased_entity[0][variation]');
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'My Super Product - Red, Small');
    $this->getSession()->getPage()->pressButton('Add to cart');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->rebuildContainer();

    $this->drupalGet($this->product->getTranslation('fr')->toUrl());
    // Use AJAX to change the size to Medium, keeping the color on Red.
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Mon super produit - FR Red, FR Small');
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][variation]', 'Mon super produit - FR Red, FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Mon super produit - FR Red, FR Medium');
    $this->assertSession()->pageTextContains('Mon super produit - FR Red, FR Medium');
    // Use AJAX to change the color to Blue, keeping the size on Medium.
    $this->getSession()->getPage()->selectFieldOption('purchased_entity[0][variation]', 'Mon super produit - FR Blue, FR Medium');
    $this->waitForAjaxToFinish();
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Mon super produit - FR Blue, FR Medium');
    $this->assertSession()->pageTextContains('Mon super produit - FR Blue, FR Medium');
    $this->getSession()->getPage()->pressButton('Add to cart');

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($this->variations[0]->getTranslation('fr'), $order_items[0]);
    $this->assertOrderItemInOrder($this->variations[5]->getTranslation('fr'), $order_items[1]);
  }

  /**
   * Tests the title widget default values with a variation url (?v=).
   */
  public function testProductVariationTitleWidgetFromUrl() {
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title',
    ]);
    $order_item_form_display->save();

    $variation = $this->variations[5];
    $this->drupalGet($variation->toUrl());
    $this->assertSession()->selectExists('purchased_entity[0][variation]');
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'My Super Product - Blue, Medium');

    // Change the site language.
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->rebuildContainer();

    $variation = $variation->getTranslation('fr');
    $this->drupalGet($variation->toUrl());
    $this->assertSession()->selectExists('purchased_entity[0][variation]');
    $this->assertAttributeSelected('purchased_entity[0][variation]', 'Mon super produit - FR Blue, FR Medium');
  }

}
