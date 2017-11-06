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
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);
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

}
