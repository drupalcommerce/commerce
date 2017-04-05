<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the "commerce_product_attributes_overview" formatter.
 *
 * @group commerce
 */
class ProductAttributesOverviewFormatterTest extends CommerceKernelTestBase {

  /**
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $productDefaultDisplay;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $attributeDefaultDisplay;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $productViewBuilder;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $this->productDefaultDisplay = commerce_get_entity_display('commerce_product', 'default', 'view');
    $this->attributeDefaultDisplay = commerce_get_entity_display('commerce_product_attribute_value', 'color', 'view');
    $this->productViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');

    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();
    $this->container->get('commerce_product.attribute_field_manager')->createField($attribute, 'default');

    EntityViewMode::create([
      'id' => 'commerce_product.catalog',
      'label' => 'Catalog',
      'targetEntityType' => 'commerce_product',
    ])->save();

    $this->container->get('router.builder')->rebuildIfNeeded();

    $attribute_values = [];
    $attribute_values['cyan'] = ProductAttributeValue::create([
      'attribute' => $attribute->id(),
      'name' => 'Cyan',
    ]);
    $attribute_values['cyan']->save();
    $attribute_values['yellow'] = ProductAttributeValue::create([
      'attribute' => $attribute->id(),
      'name' => 'Yellow',
    ]);
    $attribute_values['yellow']->save();

    $this->attributeDefaultDisplay->setComponent('name', [
      'label' => 'above',
    ]);
    $this->attributeDefaultDisplay->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $attribute_values['cyan'],
    ]);
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $attribute_values['yellow'],
    ]);
    $variation3 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $attribute_values['yellow'],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation1, $variation2, $variation3],
    ]);
    $this->product->save();
  }

  /**
   * Test the formatters rendered display.
   */
  public function testFormatterDisplay() {
    $this->productDefaultDisplay->setComponent('variations', [
      'type' => 'commerce_product_attributes_overview',
      'settings' => [
        'attributes' => ['color' => 'color'],
        'view_mode' => 'default',
      ],
    ]);
    $this->productDefaultDisplay->save();

    $build = $this->productViewBuilder->view($this->product);
    $this->render($build);

    $this->assertFieldByXPath('//h3[text()=\'Color\']');
    $this->assertFieldByXPath('//ul/li[1]/a/div/div/div[text()=\'Name\']');
    $this->assertFieldByXPath('//ul/li[1]/a/div/div/div[text()=\'Cyan\']');
    $this->assertFieldByXPath('//ul/li[2]/a/div/div/div[text()=\'Name\']');
    $this->assertFieldByXPath('//ul/li[2]/a/div/div/div[text()=\'Yellow\']');

    // Ensure Yellow rendered once, even though two variations have it.
    $this->assertEquals(1, count($this->xpath('//ul/li[2]/a/div/div/div[text()=\'Yellow\']')));

    $this->attributeDefaultDisplay->setComponent('name', [
      'label' => 'hidden',
    ]);
    $this->attributeDefaultDisplay->save();
    $this->productViewBuilder->resetCache([$this->product]);

    $build = $this->productViewBuilder->view($this->product);
    $this->render($build);

    $this->assertFieldByXPath('//h3[text()=\'Color\']');
    $this->assertFieldByXPath('//ul/li[1]/a/div/div[text()=\'Cyan\']');
    $this->assertFieldByXPath('//ul/li[2]/a/div/div[text()=\'Yellow\']');

    EntityViewMode::create([
      'id' => 'commerce_product_attribute_value.test_display',
      'label' => 'Test Display',
      'targetEntityType' => 'commerce_product_attribute_value',
    ])->save();
    $test_attribute_display_mode = $this->attributeDefaultDisplay->createCopy('test_display');
    $test_attribute_display_mode->setStatus(TRUE);
    $test_attribute_display_mode->setComponent('name', [
      'label' => 'inline',
    ]);
    $test_attribute_display_mode->save();

    $this->productDefaultDisplay->setComponent('variations', [
      'type' => 'commerce_product_attributes_overview',
      'settings' => [
        'attributes' => ['color' => 'color'],
        'view_mode' => 'test_display',
      ],
    ]);
    $this->productDefaultDisplay->save();

    $this->productViewBuilder->resetCache([$this->product]);

    $build = $this->productViewBuilder->view($this->product);
    $this->render($build);

    $this->assertFieldByXPath('//h3[text()=\'Color\']');
    $this->assertFieldByXPath('//ul/li[1]/a/div/div/div[text()=\'Name\']');
    $this->assertFieldByXPath('//ul/li[1]/a/div/div/div[text()=\'Cyan\']');
    $this->assertFieldByXPath('//ul/li[2]/a/div/div/div[text()=\'Name\']');
    $this->assertFieldByXPath('//ul/li[2]/a/div/div/div[text()=\'Yellow\']');
  }

}
