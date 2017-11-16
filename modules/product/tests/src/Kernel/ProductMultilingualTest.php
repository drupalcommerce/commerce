<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product and variation entity in a multilingual context.
 *
 * @group commerce
 */
class ProductMultilingualTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig(['commerce_product']);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('sr')->save();
  }

  /**
   * Tests that the products's stores are translated to specified language.
   */
  public function testProductStoresTranslated() {
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_store', 'online', TRUE);
    $this->store = $this->reloadEntity($this->store);
    $this->store->addTranslation('fr', [
      'name' => 'Magasin par défaut',
    ])->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'stores' => [$this->store],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->addTranslation('sr', [
      'title' => 'Мој супер производ',
    ]);

    $stores = $product->getStores();
    $this->assertEquals('Default store', reset($stores)->label());

    $stores = $product->getTranslation('fr')->getStores();
    $this->assertEquals('Magasin par défaut', reset($stores)->label());

    $stores = $product->getTranslation('en')->getStores();
    $this->assertEquals('Default store', reset($stores)->label());

    $stores = $product->getTranslation('sr')->getStores();
    $this->assertEquals('Default store', reset($stores)->label());
  }

  /**
   * Tests that the product's variations are translated to specified language.
   */
  public function testProductVariationsTranslated() {
    $default_variation_type = ProductVariationType::load('default');
    $default_variation_type->setGenerateTitle(FALSE);
    $default_variation_type->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', 'default', TRUE);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'stores' => [$this->store],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->addTranslation('sr', [
      'title' => 'Мој супер производ',
    ]);
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version one',
    ]);
    $variation1->addTranslation('fr', [
      'title' => 'Version une',
    ]);
    $product->addVariation($variation1);
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version two',
    ]);
    $variation2->addTranslation('fr', [
      'title' => 'Version deux',
    ]);
    $product->addVariation($variation2);

    $default_langcode = $this->container
      ->get('language_manager')
      ->getCurrentLanguage()->getId();

    foreach ($product->getVariations() as $variation) {
      $this->assertEquals($default_langcode, $variation->language()->getId());
    }

    foreach ($product->getTranslation('fr')->getVariations() as $variation) {
      $this->assertEquals('fr', $variation->language()->getId());
    }

    foreach ($product->getTranslation('en')->getVariations() as $variation) {
      $this->assertEquals('en', $variation->language()->getId());
    }

    foreach ($product->getTranslation('sr')->getVariations() as $variation) {
      $this->assertEquals('en', $variation->language()->getId());
    }
  }

  /**
   * Tests that the product's default variation returned in specified language.
   */
  public function testDefaultVariationTranslated() {
    $default_variation_type = ProductVariationType::load('default');
    $default_variation_type->setGenerateTitle(FALSE);
    $default_variation_type->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', 'default', TRUE);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'stores' => [$this->store],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->addTranslation('sr', [
      'title' => 'Мој супер производ',
    ]);
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version one',
    ]);
    $variation1->addTranslation('fr', [
      'title' => 'Version une',
    ]);
    $product->addVariation($variation1);
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version two',
    ]);
    $variation2->addTranslation('fr', [
      'title' => 'Version deux',
    ]);
    $product->addVariation($variation2);

    $this->assertEquals('Version one', $product->getDefaultVariation()->label());
    $this->assertEquals('Version une', $product->getTranslation('fr')->getDefaultVariation()->label());
    $this->assertEquals('Version one', $product->getTranslation('en')->getDefaultVariation()->label());
    $this->assertEquals('Version one', $product->getTranslation('sr')->getDefaultVariation()->label());
  }

  /**
   * Tests that a variation's product is returned in specified language.
   */
  public function testVariationGetProductTranslated() {
    $default_variation_type = ProductVariationType::load('default');
    $default_variation_type->setGenerateTitle(FALSE);
    $default_variation_type->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', 'default', TRUE);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'stores' => [$this->store],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version one',
      'product_id' => $product,
    ]);
    $variation1->addTranslation('fr', [
      'title' => 'Version une',
    ]);
    $variation1->addTranslation('sr', [
      'title' => 'Верзија два',
    ]);
    $product->addVariation($variation1);

    $this->assertEquals('My Super Product', $variation1->getProduct()->label());
    $this->assertEquals('Mon super produit', $variation1->getTranslation('fr')->getProduct()->label());
    $this->assertEquals('My Super Product', $variation1->getTranslation('en')->getProduct()->label());
    $this->assertEquals('My Super Product', $variation1->getTranslation('sr')->getProduct()->label());
  }

  /**
   * Tests that a variation's attributes are returned in specified language.
   */
  public function testVariationAttributeValuesTranslated() {
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', 'default', TRUE);

    $color = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color->save();
    $this->container
      ->get('commerce_product.attribute_field_manager')
      ->createField($color, 'default');

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_attribute_value', 'color', TRUE);

    $black = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ]);
    $black->addTranslation('fr', [
      'name' => 'Noir',
    ]);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'stores' => [$this->store],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'title' => 'Version one',
      'product_id' => $product,
      'attribute_color' => $black,
    ]);
    $variation1->addTranslation('fr', [
      'title' => 'Version une',
    ]);
    $variation1->addTranslation('sr', [
      'title' => 'Верзија два',
    ]);
    $product->addVariation($variation1);

    $values = $variation1->getAttributeValues();
    $this->assertEquals('Black', $values['attribute_color']->label());

    $values = $variation1->getTranslation('fr')->getAttributeValues();
    $this->assertEquals('Noir', $values['attribute_color']->label());

    $values = $variation1->getTranslation('en')->getAttributeValues();
    $this->assertEquals('Black', $values['attribute_color']->label());

    $values = $variation1->getTranslation('sr')->getAttributeValues();
    $this->assertEquals('Black', $values['attribute_color']->label());

    $this->assertEquals('Black', $variation1->getAttributeValue('attribute_color')->label());
    $this->assertEquals('Noir', $variation1->getTranslation('fr')->getAttributeValue('attribute_color')->label());
    $this->assertEquals('Black', $variation1->getTranslation('en')->getAttributeValue('attribute_color')->label());
    $this->assertEquals('Black', $variation1->getTranslation('sr')->getAttributeValue('attribute_color')->label());
  }

}
