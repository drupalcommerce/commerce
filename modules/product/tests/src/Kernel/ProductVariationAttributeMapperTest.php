<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product variation attribute mapper.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductVariationAttributeMapper
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
  ];

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The variation attribute value mapper.
   *
   * @var \Drupal\commerce_product\ProductVariationAttributeMapperInterface
   */
  protected $mapper;

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
    $this->mapper = $this->container->get('commerce_product.variation_attribute_mapper');
    $variation_type = ProductVariationType::load('default');

    $this->colorAttributes = $this->createAttributeSet($variation_type, 'color', [
      'black' => 'Black',
      'blue' => 'Blue',
      'green' => 'Green',
      'red' => 'Red',
      'white' => 'White',
      'yellow' => 'Yellow',
    ]);
    $this->sizeAttributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $this->ramAttributes = $this->createAttributeSet($variation_type, 'ram', [
      '4gb' => '4GB',
      '8gb' => '8GB',
      '16gb' => '16GB',
      '32gb' => '32GB',
    ]);
    $this->disk1Attributes = $this->createAttributeSet($variation_type, 'disk1', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ]);
    $this->disk2Attributes = $this->createAttributeSet($variation_type, 'disk2', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ], FALSE);

    $user = $this->createUser([], ['administer commerce_product']);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Tests selecting a variation.
   *
   * @covers ::selectVariation
   */
  public function testSelect() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    // No attribute values.
    $selected_variation = $this->mapper->selectVariation($product->getVariations());
    $this->assertNull($selected_variation);

    // Empty attribute values.
    $selected_variation = $this->mapper->selectVariation($product->getVariations(), [
      'attribute_color' => '',
      'attribute_size' => '',
    ]);
    $this->assertNull($selected_variation);

    // Missing first attribute.
    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_color' => '',
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertNull($selected_variation);

    // Single attribute value.
    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[3]->id(), $selected_variation->id());

    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $selected_variation->id());

    // Two attribute values.
    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_color' => $this->colorAttributes['red']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $selected_variation->id());

    // Invalid attribute combination.
    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[3]->id(), $selected_variation->id());
    $this->assertEquals('Blue', $selected_variation->getAttributeValue('attribute_color')->label());
    $this->assertEquals('Small', $selected_variation->getAttributeValue('attribute_size')->label());
  }

  /**
   * Tests selecting a variation when there are optional attributes.
   *
   * @covers ::selectVariation
   */
  public function testSelectWithOptionalAttributes() {
    $product = $this->generateThreeByTwoOptionalScenario();
    $variations = $product->getVariations();

    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
    ]);
    $this->assertEquals($variations[1]->id(), $selected_variation->id());

    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['1tb']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $selected_variation->id());

    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['2tb']->id(),
    ]);
    // Falls back to 16GBx1TB, 16GBx1TBx2TB is invalid.
    $this->assertEquals($variations[1]->id(), $selected_variation->id());
  }

  /**
   * Tests preparing attributes.
   *
   * @covers ::prepareAttributes
   */
  public function testPrepareAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    // Test from the initial variation.
    $attributes = $this->mapper->prepareAttributes(reset($variations), $variations);

    $color_attribute = $attributes['attribute_color'];
    $this->assertEquals('color', $color_attribute->getId());
    $this->assertEquals('Color', $color_attribute->getLabel());
    $this->assertEquals('select', $color_attribute->getElementType());
    $this->assertTrue($color_attribute->isRequired());
    $this->assertEquals(['2' => 'Blue', '4' => 'Red'], $color_attribute->getValues());

    $size_attribute = $attributes['attribute_size'];
    $this->assertEquals('size', $size_attribute->getId());
    $this->assertEquals('Size', $size_attribute->getLabel());
    $this->assertEquals('select', $size_attribute->getElementType());
    $this->assertTrue($size_attribute->isRequired());
    $this->assertEquals(['7' => 'Small', '8' => 'Medium', '9' => 'Large'], $size_attribute->getValues());

    // Test Blue Medium.
    $attributes = $this->mapper->prepareAttributes($variations[4], $variations);

    $color_attribute = $attributes['attribute_color'];
    $this->assertEquals('color', $color_attribute->getId());
    $this->assertEquals('Color', $color_attribute->getLabel());
    $this->assertEquals('select', $color_attribute->getElementType());
    $this->assertTrue($color_attribute->isRequired());
    $this->assertEquals(['2' => 'Blue', '4' => 'Red'], $color_attribute->getValues());

    $size_attribute = $attributes['attribute_size'];
    $this->assertEquals('size', $size_attribute->getId());
    $this->assertEquals('Size', $size_attribute->getLabel());
    $this->assertEquals('select', $size_attribute->getElementType());
    $this->assertTrue($size_attribute->isRequired());
    $this->assertEquals(['7' => 'Small', '8' => 'Medium'], $size_attribute->getValues());
  }

  /**
   * Tests preparing attributes when there are optional attributes.
   *
   * @covers ::prepareAttributes
   */
  public function testPrepareAttributesOptional() {
    $product = $this->generateThreeByTwoOptionalScenario();
    $variations = $product->getVariations();

    // Test from the initial variation.
    $attributes = $this->mapper->prepareAttributes(reset($variations), $variations);

    $ram_attribute = $attributes['attribute_ram'];
    $this->assertEquals('ram', $ram_attribute->getId());
    $this->assertEquals('Ram', $ram_attribute->getLabel());
    $this->assertEquals('select', $ram_attribute->getElementType());
    $this->assertTrue($ram_attribute->isRequired());
    $this->assertEquals(['11' => '8GB', '12' => '16GB'], $ram_attribute->getValues());

    $disk1_attribute = $attributes['attribute_disk1'];
    $this->assertEquals('disk1', $disk1_attribute->getId());
    $this->assertEquals('Disk1', $disk1_attribute->getLabel());
    $this->assertEquals('select', $disk1_attribute->getElementType());
    $this->assertTrue($disk1_attribute->isRequired());
    $this->assertEquals(['14' => '1TB'], $disk1_attribute->getValues());

    // The Disk 2 1TB option should not show. Only "none".
    // The default variation is 8GB x 1TB, which does not have the Disk 2 value
    // so it should only return "_none". The Disk 2 option should have only have
    // this option is the 16GB RAM option is chosen.
    $disk2_attribute = $attributes['attribute_disk2'];
    $this->assertEquals('disk2', $disk2_attribute->getId());
    $this->assertEquals('Disk2', $disk2_attribute->getLabel());
    $this->assertEquals('select', $disk2_attribute->getElementType());
    $this->assertFalse($disk2_attribute->isRequired());
    $this->assertEquals(['_none' => ''], $disk2_attribute->getValues());

    // Test from the 16GB x 1TB x None variation.
    $attributes = $this->mapper->prepareAttributes($variations[1], $variations);

    $ram_attribute = $attributes['attribute_ram'];
    $this->assertEquals('ram', $ram_attribute->getId());
    $this->assertEquals('Ram', $ram_attribute->getLabel());
    $this->assertEquals('select', $ram_attribute->getElementType());
    $this->assertTrue($ram_attribute->isRequired());
    $this->assertEquals(['11' => '8GB', '12' => '16GB'], $ram_attribute->getValues());

    $disk1_attribute = $attributes['attribute_disk1'];
    $this->assertEquals('disk1', $disk1_attribute->getId());
    $this->assertEquals('Disk1', $disk1_attribute->getLabel());
    $this->assertEquals('select', $disk1_attribute->getElementType());
    $this->assertTrue($disk1_attribute->isRequired());
    $this->assertEquals(['14' => '1TB'], $disk1_attribute->getValues());

    $disk2_attribute = $attributes['attribute_disk2'];
    $this->assertEquals('disk2', $disk2_attribute->getId());
    $this->assertEquals('Disk2', $disk2_attribute->getLabel());
    $this->assertEquals('select', $disk2_attribute->getElementType());
    $this->assertFalse($disk2_attribute->isRequired());
    $this->assertEquals(['_none' => '', '17' => '1TB'], $disk2_attribute->getValues());
  }

  /**
   * Tests preparing attributes when the values are mutually exclusive.
   *
   * @covers ::prepareAttributes
   */
  public function testMutuallyExclusiveAttributeMatrixTwoByTwoByTwo() {
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

    // Test from the initial variation.
    $attributes = $this->mapper->prepareAttributes(reset($variations), $variations);

    $ram_attribute = $attributes['attribute_ram'];
    $this->assertEquals('ram', $ram_attribute->getId());
    $this->assertEquals('Ram', $ram_attribute->getLabel());
    $this->assertEquals('select', $ram_attribute->getElementType());
    $this->assertTrue($ram_attribute->isRequired());
    $this->assertEquals(['11' => '8GB', '10' => '4GB'], $ram_attribute->getValues());

    $disk1_attribute = $attributes['attribute_disk1'];
    $this->assertEquals('disk1', $disk1_attribute->getId());
    $this->assertEquals('Disk1', $disk1_attribute->getLabel());
    $this->assertEquals('select', $disk1_attribute->getElementType());
    $this->assertTrue($disk1_attribute->isRequired());
    $this->assertNotCount(3, $disk1_attribute->getValues(), 'Out of the three available attribute values, only the one used is returned.');
    $this->assertCount(1, $disk1_attribute->getValues());
    $this->assertEquals(['15' => '2TB'], $disk1_attribute->getValues());

    $disk2_attribute = $attributes['attribute_disk2'];
    $this->assertEquals('disk2', $disk2_attribute->getId());
    $this->assertEquals('Disk2', $disk2_attribute->getLabel());
    $this->assertEquals('select', $disk2_attribute->getElementType());
    $this->assertFalse($disk2_attribute->isRequired());
    $this->assertEquals(['18' => '2TB'], $disk2_attribute->getValues());

    // Test 8GB x 1TB x 2TB.
    $attributes = $this->mapper->prepareAttributes($variations[1], $variations);

    $ram_attribute = $attributes['attribute_ram'];
    $this->assertEquals('ram', $ram_attribute->getId());
    $this->assertEquals('Ram', $ram_attribute->getLabel());
    $this->assertEquals('select', $ram_attribute->getElementType());
    $this->assertTrue($ram_attribute->isRequired());
    $this->assertNotCount(4, $ram_attribute->getValues(), 'Out of the four available attribute values, only the two used are returned.');
    $this->assertCount(2, $ram_attribute->getValues());
    $this->assertEquals(['11' => '8GB', '10' => '4GB'], $ram_attribute->getValues());

    $disk1_attribute = $attributes['attribute_disk1'];
    $this->assertEquals('disk1', $disk1_attribute->getId());
    $this->assertEquals('Disk1', $disk1_attribute->getLabel());
    $this->assertEquals('select', $disk1_attribute->getElementType());
    $this->assertTrue($disk1_attribute->isRequired());
    $this->assertEquals(['15' => '2TB', '14' => '1TB'], $disk1_attribute->getValues());

    $disk2_attribute = $attributes['attribute_disk2'];
    $this->assertEquals('disk2', $disk2_attribute->getId());
    $this->assertEquals('Disk2', $disk2_attribute->getLabel());
    $this->assertEquals('select', $disk2_attribute->getElementType());
    $this->assertFalse($disk2_attribute->isRequired());
    // There should only be one Disk 2 option, since the other 8GB RAM option
    // has a Disk 1 value of 2TB.
    $this->assertEquals(['18' => '2TB'], $disk2_attribute->getValues());

    // Test 8GB x 2TB x 1TB.
    $attributes = $this->mapper->prepareAttributes($variations[2], $variations);

    $ram_attribute = $attributes['attribute_ram'];
    $this->assertEquals('ram', $ram_attribute->getId());
    $this->assertEquals('Ram', $ram_attribute->getLabel());
    $this->assertEquals('select', $ram_attribute->getElementType());
    $this->assertTrue($ram_attribute->isRequired());
    $this->assertEquals(['11' => '8GB', '10' => '4GB'], $ram_attribute->getValues());

    $disk1_attribute = $attributes['attribute_disk1'];
    $this->assertEquals('disk1', $disk1_attribute->getId());
    $this->assertEquals('Disk1', $disk1_attribute->getLabel());
    $this->assertEquals('select', $disk1_attribute->getElementType());
    $this->assertTrue($disk1_attribute->isRequired());
    $this->assertEquals(['15' => '2TB', '14' => '1TB'], $disk1_attribute->getValues());

    $disk2_attribute = $attributes['attribute_disk2'];
    $this->assertEquals('disk2', $disk2_attribute->getId());
    $this->assertEquals('Disk2', $disk2_attribute->getLabel());
    $this->assertEquals('select', $disk2_attribute->getElementType());
    $this->assertFalse($disk2_attribute->isRequired());
    // There should only be one Disk 2 option, since the other 8GB RAM option
    // has a Disk 1 value of 2TB.
    $this->assertEquals(['17' => '1TB'], $disk2_attribute->getValues());
  }

  /**
   * Tests having three attributes and six variations.
   *
   * @covers ::selectVariation
   * @covers ::prepareAttributes
   */
  public function testThreeAttributesSixVariations() {
    $variation_type = ProductVariationType::load('default');

    $pack = $this->createAttributeSet($variation_type, 'pack', [
      'one' => '1',
      'twenty' => '20',
      'hundred' => '100',
      'twohundred' => '200',
    ]);

    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $product->save();

    // The Size attribute needs a lighter weight than Color for this scenario.
    // @todo This is an undocumented item, where the order of the attributes on
    // the form display correlate to how they display in the widget / returned
    // values.
    $form_display = commerce_get_entity_display('commerce_product_variation', $variation_type->id(), 'form');
    $form_display->setComponent('attribute_size', ['weight' => 0] + $form_display->getComponent('attribute_size'));
    $form_display->setComponent('attribute_color', ['weight' => 1] + $form_display->getComponent('attribute_color'));
    $form_display->setComponent('attribute_pack', ['weight' => 2] + $form_display->getComponent('attribute_pack'));
    $form_display->save();

    $attribute_values_matrix = [
      ['small', 'black', 'one'],
      ['small', 'blue', 'twenty'],
      ['medium', 'green', 'hundred'],
      ['medium', 'red', 'twohundred'],
      ['large', 'white', 'hundred'],
      ['large', 'yellow', 'twenty'],
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
        'attribute_size' => $this->sizeAttributes[$value[0]],
        'attribute_color' => $this->colorAttributes[$value[1]],
        'attribute_pack' => $pack[$value[2]],
      ]);
      $variation->save();
      $variations[] = $variation;
      $product->addVariation($variation);
    }
    $product->save();

    // Verify available attribute selections for the default variation.
    $selected_variation = $product->getDefaultVariation();
    $attributes = $this->mapper->prepareAttributes($selected_variation, $product->getVariations());
    $size_attribute = $attributes['attribute_size'];
    $this->assertEquals(['7' => 'Small', '8' => 'Medium', '9' => 'Large'], $size_attribute->getValues());
    $color_attribute = $attributes['attribute_color'];
    $this->assertEquals(['2' => 'Blue', '1' => 'Black'], $color_attribute->getValues());
    $pack_attribute = $attributes['attribute_pack'];
    // The resolved variation is Small -> Black -> 1, cannot choose 20 for the
    // pack size, since that is Small -> Blue -> 20.
    $this->assertEquals(['20' => '1'], $pack_attribute->getValues());

    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_size' => $this->sizeAttributes['small']->id(),
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[1]->id(), $selected_variation->id());

    // Medium only has Green & Red as color, so selecting this size should
    // cause the color to reset.
    $selected_variation = $this->mapper->selectVariation($variations, [
      'attribute_size' => $this->sizeAttributes['medium']->id(),
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $selected_variation->id());
    $this->assertEquals('Medium', $selected_variation->getAttributeValue('attribute_size')->label());
    $this->assertEquals('Green', $selected_variation->getAttributeValue('attribute_color')->label());
    $this->assertEquals('100', $selected_variation->getAttributeValue('attribute_pack')->label());

    // Verify available attribute selections.
    $attributes = $this->mapper->prepareAttributes($selected_variation, $product->getVariations());
    $size_attribute = $attributes['attribute_size'];
    $this->assertEquals(['7' => 'Small', '8' => 'Medium', '9' => 'Large'], $size_attribute->getValues());
    $color_attribute = $attributes['attribute_color'];
    $this->assertEquals(['3' => 'Green', '4' => 'Red'], $color_attribute->getValues());
    $pack_attribute = $attributes['attribute_pack'];
    // The resolved variation is Medium -> Green -> 100, cannot choose 200 for
    // the pack size, since that is Medium -> Red -> 200.
    $this->assertEquals(['22' => '100'], $pack_attribute->getValues());
  }

  /**
   * Generates a three by two scenario.
   *
   * There are three sizes and two colors. Missing one color option.
   * Generated product variations:
   *   [ RS, RM, RL ]
   *   [ BS, BM, X  ]
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
   * Generates a three by two (optional) scenario.
   *
   * Generated product variations:
   *   [ 8GBx1TB,    X        , X ]
   *   [    X   , 16GBx1TB    , X ]
   *   [    X   , 16GBx1TBx1TB, X ]
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
   * @param bool $required
   *   Whether the created attribute should be required.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet(ProductVariationTypeInterface $variation_type, $name, array $options, $required = TRUE) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type->id());
    // The field is always created as required by default.
    if (!$required) {
      $field = FieldConfig::loadByName('commerce_product_variation', $variation_type->id(), 'attribute_' . $name);
      $field->setRequired(FALSE);
      $field->save();
    }

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
