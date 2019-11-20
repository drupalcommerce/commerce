<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product variation title generation.
 *
 * @group commerce
 */
class ProductVariationGeneratedTitleTest extends CommerceKernelTestBase {

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
   * The test variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $variationType;

  /**
   * The test product type.
   *
   * @var \Drupal\commerce_product\Entity\ProductType
   */
  protected $productType;

  /**
   * The test attribute.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttribute
   */
  protected $attribute;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig(['commerce_product']);

    ConfigurableLanguage::createFromLangcode('fr')->save();

    $variation_type = ProductVariationType::create([
      'id' => 'generate_title',
      'label' => 'Generate title test',
      'orderItemType' => 'default',
      'generateTitle' => TRUE,
    ]);
    $variation_type->save();
    $this->variationType = $variation_type;

    $product_type = ProductType::create([
      'id' => 'generate_title',
      'label' => 'Generate title test',
      'variationType' => $variation_type->id(),
    ]);
    $product_type->save();
    $this->productType = $product_type;

    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color_attribute->save();
    $this->container
      ->get('commerce_product.attribute_field_manager')
      ->createField($color_attribute, $this->variationType->id());
    $this->attribute = $color_attribute;
  }

  /**
   * Tests the title is generated.
   */
  public function testTitleGenerated() {
    // Variations without a product have no title, because it can not be
    // determined.
    $variation = ProductVariation::create([
      'type' => $this->variationType->id(),
    ]);
    $variation->save();
    $this->assertNull($variation->label());

    // When variations have a product, but no attributes, the variation label
    // should be the product's.
    $product = Product::create([
      'type' => $this->productType->id(),
      'title' => 'My Super Product',
      'variations' => [$variation],
    ]);
    $product->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->reloadEntity($variation);
    $this->assertEquals($variation->label(), $product->label());

    // With attribute values, the variation title should be the product plus all
    // of its attributes.
    $color_black = ProductAttributeValue::create([
      'attribute' => $this->attribute->id(),
      'name' => 'Black',
      'weight' => 3,
    ]);
    $color_black->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->reloadEntity($variation);
    $variation->get('attribute_color')->setValue($color_black);
    $variation->save();

    $this->assertNotEquals($variation->label(), $product->label());
    $this->assertEquals($variation->label(), sprintf('%s - %s', $product->label(), $color_black->label()));
  }

  /**
   * Tests that creating a new variation creates a translated title.
   */
  public function testMultilingualTitle() {
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', $this->variationType->id(), TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', $this->productType->id(), TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_attribute_value', $this->attribute->id(), TRUE);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => $this->variationType->id(),
    ]);
    $variation->save();
    $this->assertNull($variation->label());
    $product = Product::create([
      'type' => $this->productType->id(),
      'title' => 'My Super Product',
      'variations' => [$variation],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->save();
    // Generating a translation of the variation should create a title which
    // has the product's translated title.
    $translation = $variation->addTranslation('fr', []);
    $translation->save();
    $this->assertEquals($product->getTranslation('fr')->label(), $variation->getTranslation('fr')->label());
    // Verify translated attributes are used in the generated title.
    $color_black = ProductAttributeValue::create([
      'attribute' => $this->attribute->id(),
      'name' => 'Black',
      'weight' => 3,
    ]);
    $color_black->save();
    $color_black->addTranslation('fr', [
      'name' => 'Noir',
    ]);
    $color_black->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->reloadEntity($variation);
    $variation->get('attribute_color')->setValue($color_black);
    $variation->save();
    $variation->getTranslation('fr')->save();
    $this->assertEquals($variation->getTranslation('fr')->label(), sprintf('%s - %s', $product->getTranslation('fr')->label(), $color_black->getTranslation('fr')->label()));
  }

}
