<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the product variation field renderer.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductVariationFieldRenderer
 *
 * @group commerce
 */
class ProductVariationFieldRendererTest extends KernelTestBase {

  /**
   * The variation field injection.
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRendererInterface
   */
  protected $variationFieldRenderer;

  /**
   * The first variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $firstVariationType;

  /**
   * The second variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $secondVariationType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system', 'field', 'options', 'user', 'path', 'text',
    'entity', 'views', 'address', 'inline_entity_form',
    'commerce', 'commerce_price', 'commerce_store', 'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);

    $this->variationFieldRenderer = $this->container->get('commerce_product.variation_field_renderer');

    $this->firstVariationType = ProductVariationType::create([
      'id' => 'shirt',
      'label' => 'Shirt',
    ]);
    $this->firstVariationType->save();
    $this->secondVariationType = ProductVariationType::create([
      'id' => 'mug',
      'label' => 'Mug',
    ]);
    $this->secondVariationType->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'render_field',
      'entity_type' => 'commerce_product_variation',
      'type' => 'text',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->secondVariationType->id(),
      'label' => 'Render field',
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->save();

    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();

    $this->container->get('commerce_product.attribute_field_manager')
      ->createField($attribute, $this->secondVariationType->id());
  }

  /**
   * @covers ::getFieldDefinitions
   */
  public function testGetFieldDefinitions() {
    $field_definitions = $this->variationFieldRenderer->getFieldDefinitions($this->firstVariationType->id());
    $field_names = array_keys($field_definitions);
    $this->assertEquals(['sku', 'title', 'price'], $field_names, 'The title, sku, price variation fields are renderable.');

    $field_definitions = $this->variationFieldRenderer->getFieldDefinitions($this->secondVariationType->id());
    $field_names = array_keys($field_definitions);
    $this->assertEquals(['sku', 'title', 'price', 'render_field', 'attribute_color'], $field_names, 'The title, sku, price, render_field, attribute_color variation fields are renderable.');
  }

  /**
   * @covers ::renderFields
   * @covers ::renderField
   */
  public function testRenderFields() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->save();

    $rendered_fields = $this->variationFieldRenderer->renderFields($variation);
    $this->assertFalse(isset($rendered_fields['statis']), 'Variation status field was not rendered');
    $this->assertTrue(isset($rendered_fields['sku']), 'Variation SKU field was rendered');
    $this->assertEquals('product--variation-field--variation_sku__' . $variation->getProductId(), $rendered_fields['sku']['#ajax_replace_class']);
  }

}
