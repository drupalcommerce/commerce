<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product variation title generation.
 *
 * @group commerce
 */
class ProductVariationAttributeMapperTest extends CommerceKernelTestBase {

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
   * The color attributes values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $colorAttributes;

  /**
   * The size attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $sizeAttributes;

  /**
   * The variation attribute value mapper.
   *
   * @var \Drupal\commerce_product\ProductVariationAttributeMapperInterface
   */
  protected $mapper;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The RAM attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $ramAttributes;

  /**
   * The Disk 1 attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $disk1Attributes;

  /**
   * The Disk 2 attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $disk2Attributes;

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
    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');
    $this->mapper = $this->container->get('commerce_product.variation_attribute_value_mapper');

    $variation_type = ProductVariationType::load('default');

    // Create attributes.
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);

    $ram_attributes = $this->createAttributeSet($variation_type, 'ram', [
      '4gb' => '4GB',
      '8gb' => '8GB',
      '16gb' => '16GB',
      '32gb' => '32GB',
    ]);

    $disk1_attributes = $this->createAttributeSet($variation_type, 'disk1', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ]);
    $disk2_attributes = $this->createAttributeSet($variation_type, 'disk2', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ]);

    $this->colorAttributes = $color_attributes;
    $this->sizeAttributes = $size_attributes;

    $this->ramAttributes = $ram_attributes;
    $this->disk1Attributes = $disk1_attributes;
    $this->disk2Attributes = $disk2_attributes;
  }

  /**
   * Tests that if no attributes are passed, the default variation is returned.
   */
  public function testResolveWithNoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $resolved_variation = $this->mapper->getVariation($product->getVariations());
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($product->getVariations(), [
      'attribute_color' => '',
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($product->getVariations(), [
      'attribute_color' => '',
      'attribute_size' => '',
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  /**
   * Tests that if one attribute passed, the proper variation is returned.
   */
  public function testResolveWithWithOneAttribute() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[3]->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());
  }

  /**
   * Tests that if two attributes are passed, the proper variation is returned.
   */
  public function testResolveWithWithTwoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_color' => $this->colorAttributes['red']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    // An invalid arrangement was passed, so the default variation is resolved.
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_color' => '',
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    // A missing attribute was passed for first option.
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['small']->id(),
    ]);
    // An empty second option defaults to first variation option.
    $this->assertEquals($variations[3]->id(), $resolved_variation->id());
  }

  /**
   * Tests optional attributes.
   */
  public function testResolveWithOptionalAttributes() {
    $product = $this->generateThreeByTwoOptionalScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
    ]);
    $this->assertEquals($variations[1]->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['1tb']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());

    $resolved_variation = $this->mapper->getVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['2tb']->id(),
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  /**
   * Tests the getAttributeValues method.
   */
  public function testGetAttributeValues() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    // With no callback, all value should be returned.
    $values = $this->mapper->getAttributeValues($variations, 'attribute_color');
    foreach ($this->colorAttributes as $color_attribute) {
      $this->assertTrue(in_array($color_attribute->label(), $values));
    }

    // With no callback, all value should be returned.
    $values = $this->mapper->getAttributeValues($variations, 'attribute_color', function (ProductVariationInterface $variation) {
      return $variation->getAttributeValueId('attribute_color') == $this->colorAttributes['blue']->id();
    });
    $this->assertTrue(in_array('Blue', $values));
    $this->assertFalse(in_array('Red', $values));
  }

  /**
   * Tests the getAttributeInfo method.
   */
  public function testGetAttributeInfo() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    // Test from initial variation.
    $attribute_info = $this->mapper->getAttributeInfo(reset($variations), $variations);

    $color_attribute_info = $attribute_info['attribute_color'];
    $this->assertEquals('select', $color_attribute_info['element_type']);
    $this->assertEquals(1, $color_attribute_info['required']);
    $this->assertCount(2, $color_attribute_info['values']);

    $size_attribute_info = $attribute_info['attribute_size'];
    $this->assertEquals('select', $size_attribute_info['element_type']);
    $this->assertEquals(1, $size_attribute_info['required']);
    $this->assertCount(3, $size_attribute_info['values']);

    // Test Blue Medium.
    $attribute_info = $this->mapper->getAttributeInfo($variations[4], $variations);

    $color_attribute_info = $attribute_info['attribute_color'];
    $this->assertEquals('select', $color_attribute_info['element_type']);
    $this->assertEquals(1, $color_attribute_info['required']);
    $this->assertCount(2, $color_attribute_info['values']);

    $size_attribute_info = $attribute_info['attribute_size'];
    $this->assertEquals('select', $size_attribute_info['element_type']);
    $this->assertEquals(1, $size_attribute_info['required']);
    $this->assertCount(2, $size_attribute_info['values']);
    $this->assertFalse(in_array('Large', $size_attribute_info['values']));
  }

  /**
   * Tests the getAttributeInfo method.
   */
  public function testGetAttributeInfoOptional() {
    $product = $this->generateThreeByTwoOptionalScenario();
    $variations = $product->getVariations();

    // Test from initial variation.
    $attribute_info = $this->mapper->getAttributeInfo(reset($variations), $variations);

    $ram_attribute_info = $attribute_info['attribute_ram'];
    $this->assertEquals('select', $ram_attribute_info['element_type']);
    $this->assertEquals(1, $ram_attribute_info['required']);
    $this->assertNotCount(4, $ram_attribute_info['values'], 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute_info['values']);

    $disk1_attribute_info = $attribute_info['attribute_disk1'];
    $this->assertEquals('select', $disk1_attribute_info['element_type']);
    $this->assertEquals(1, $disk1_attribute_info['required']);
    $this->assertNotCount(3, $disk1_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(1, $disk1_attribute_info['values']);

    // @todo The Disk 2 1TB option should not show. Only "none"
    // This returns disk2 [ [ '_none' => '', 13 => '1TB' ] ]
    //
    // The default variation is 8GB x 1TB, which does not have the Disk 2 value
    // so it should only return "_none". The Disk 2 option should have only have
    // this option is the 16GB RAM option is chosen.
    $disk2_attribute_info = $attribute_info['attribute_disk2'];
    $this->assertEquals('select', $disk2_attribute_info['element_type']);
    $this->assertEquals(1, $disk2_attribute_info['required']);
    $this->assertNotCount(3, $disk2_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    // There are two values. Since this is optional there is a "_none" option.
    $this->assertCount(1, $disk2_attribute_info['values']);
    $this->assertTrue(isset($disk2_attribute_info['values']['_none']));

    // Test from with 16GB which has a variation with option.
    $attribute_info = $this->mapper->getAttributeInfo($variations[1], $variations);

    $ram_attribute_info = $attribute_info['attribute_ram'];
    $this->assertEquals('select', $ram_attribute_info['element_type']);
    $this->assertEquals(1, $ram_attribute_info['required']);
    $this->assertNotCount(4, $ram_attribute_info['values'], 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute_info['values']);

    $disk1_attribute_info = $attribute_info['attribute_disk1'];
    $this->assertEquals('select', $disk1_attribute_info['element_type']);
    $this->assertEquals(1, $disk1_attribute_info['required']);
    $this->assertNotCount(3, $disk1_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(1, $disk1_attribute_info['values']);

    $disk2_attribute_info = $attribute_info['attribute_disk2'];
    $this->assertEquals('select', $disk2_attribute_info['element_type']);
    $this->assertEquals(1, $disk2_attribute_info['required']);
    $this->assertNotCount(3, $disk2_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    // There are two values. Since this is optional there is a "_none" option.
    $this->assertCount(2, $disk2_attribute_info['values']);
    $this->assertTrue(isset($disk2_attribute_info['values']['_none']));
  }

  /**
   * Tests the getAttributeInfo method.
   *
   * @group debug
   */
  public function testMutuallyExclusiveAttributeMatrixTwoByTwobyTwo() {
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $attribute_values_matrix = [
      ['4gb', '2tb', '2tb'],
      ['8gb', '1tb', '2tb'],
      ['8gb', '2tb', '1tb'],
    ];
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_ram' => $this->ramAttributes[$value[0]],
        'attribute_disk1' => $this->disk1Attributes[$value[1]],
        'attribute_disk2' => isset($this->disk2Attributes[$value[2]]) ? $this->disk2Attributes[$value[2]] : NULL,
      ]);
      $variation->save();
      $variations[] = $variation;
      $product->addVariation($variation);
    }
    $product->save();

    // Test from initial variation.
    $attribute_info = $this->mapper->getAttributeInfo(reset($variations), $variations);

    $ram_attribute_info = $attribute_info['attribute_ram'];
    $this->assertEquals('select', $ram_attribute_info['element_type']);
    $this->assertEquals(1, $ram_attribute_info['required']);
    $this->assertNotCount(4, $ram_attribute_info['values'], 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute_info['values']);

    $disk1_attribute_info = $attribute_info['attribute_disk1'];
    $this->assertEquals('select', $disk1_attribute_info['element_type']);
    $this->assertEquals(1, $disk1_attribute_info['required']);
    $this->assertNotCount(3, $disk1_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(1, $disk1_attribute_info['values']);

    $disk2_attribute_info = $attribute_info['attribute_disk2'];
    $this->assertEquals('select', $disk2_attribute_info['element_type']);
    $this->assertEquals(1, $disk2_attribute_info['required']);
    $this->assertNotCount(3, $disk2_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(1, $disk2_attribute_info['values']);
    $this->assertTrue(in_array('2TB', $disk2_attribute_info['values']), 'Only the one valid Disk 2 option is available.');

    // Test 8GB 1TB 2TB.
    $attribute_info = $this->mapper->getAttributeInfo($variations[1], $variations);

    $ram_attribute_info = $attribute_info['attribute_ram'];
    $this->assertEquals('select', $ram_attribute_info['element_type']);
    $this->assertEquals(1, $ram_attribute_info['required']);
    $this->assertNotCount(4, $ram_attribute_info['values'], 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute_info['values']);

    $disk1_attribute_info = $attribute_info['attribute_disk1'];
    $this->assertEquals('select', $disk1_attribute_info['element_type']);
    $this->assertEquals(1, $disk1_attribute_info['required']);
    $this->assertNotCount(3, $disk1_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(2, $disk1_attribute_info['values']);

    $disk2_attribute_info = $attribute_info['attribute_disk2'];
    $this->assertEquals('select', $disk2_attribute_info['element_type']);
    $this->assertEquals(1, $disk2_attribute_info['required']);
    $this->assertNotCount(3, $disk2_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    // There should only be one Disk 2 option, since the other 8GB RAM option
    // has a Disk 1 value of 2TB.
    $this->assertCount(1, $disk2_attribute_info['values']);

    // Test 8GB 2TB 1TB.
    $attribute_info = $this->mapper->getAttributeInfo($variations[2], $variations);

    $ram_attribute_info = $attribute_info['attribute_ram'];
    $this->assertEquals('select', $ram_attribute_info['element_type']);
    $this->assertEquals(1, $ram_attribute_info['required']);
    $this->assertNotCount(4, $ram_attribute_info['values'], 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute_info['values']);

    $disk1_attribute_info = $attribute_info['attribute_disk1'];
    $this->assertEquals('select', $disk1_attribute_info['element_type']);
    $this->assertEquals(1, $disk1_attribute_info['required']);
    $this->assertNotCount(3, $disk1_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(2, $disk1_attribute_info['values']);

    $disk2_attribute_info = $attribute_info['attribute_disk2'];
    $this->assertEquals('select', $disk2_attribute_info['element_type']);
    $this->assertEquals(1, $disk2_attribute_info['required']);
    $this->assertNotCount(3, $disk2_attribute_info['values'], 'Out of the three available attribute values, only the one used is returned.');
    // There should only be one Disk 2 option, since the other 8GB RAM option
    // has a Disk 1 value of 2TB.
    $this->assertCount(1, $disk2_attribute_info['values'], print_r($disk2_attribute_info['values'], TRUE));
  }

  /**
   * Generates a three by two scenario.
   *
   * This generates a product and variations in 3x2 scenario. There are three
   * sizes and two colors. Missing one color option.
   *
   * [ RS, RM, RL ]
   * [ BS, BM, X  ]
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
  protected function generateThreeByTwoScenario() {
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_color' => $this->colorAttributes[$value[0]],
        'attribute_size' => $this->sizeAttributes[$value[1]],
      ]);
      $variation->save();
      $variations[] = $variation;
      $product->addVariation($variation);
    }
    $product->save();

    return $product;
  }

  /**
   * Generates a three by two (optional) secenario.
   *
   * This generates a product and variations in 3x2 scenario.
   *
   * https://www.drupal.org/project/commerce/issues/2730643#comment-11216983
   *
   * [ 8GBx1TB,    X        , X ]
   * [    X   , 16GBx1TB    , X ]
   * [    X   , 16GBx1TBx1TB, X ]
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
  protected function generateThreeByTwoOptionalScenario() {
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $attribute_values_matrix = [
      ['8gb', '1tb', ''],
      ['16gb', '1tb', ''],
      ['16gb', '1tb', '1tb'],
    ];
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_ram' => $this->ramAttributes[$value[0]],
        'attribute_disk1' => $this->disk1Attributes[$value[1]],
        'attribute_disk2' => isset($this->disk2Attributes[$value[2]]) ? $this->disk2Attributes[$value[2]] : NULL,
      ]);
      $variation->save();
      $variations[] = $variation;
      $product->addVariation($variation);
    }
    $product->save();

    return $product;
  }

  /**
   * Creates an attribute field and set of attribute values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   * @param string $name
   *   The attribute field name.
   * @param array $options
   *   Associative array of key name values. [red => Red].
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet(ProductVariationTypeInterface $variation_type, $name, array $options) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type->id());

    $attribute_set = [];
    foreach ($options as $key => $value) {
      $attribute_set[$key] = $this->createAttributeValue($name, $value);
    }

    return $attribute_set;
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute ID.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The attribute value entity.
   */
  protected function createAttributeValue($attribute, $name) {
    $attribute_value = ProductAttributeValue::create([
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
  }

}
