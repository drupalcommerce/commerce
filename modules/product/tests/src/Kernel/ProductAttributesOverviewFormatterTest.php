<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\ProductTestTrait;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests using the "commerce_product_attributes_overview" formatter.
 *
 * @group commerce
 */
class ProductAttributesOverviewFormatterTest extends KernelTestBase {

  use ProductTestTrait;

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
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface.
   */
  protected $productViewBuilder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path', 'text',
    'entity', 'views', 'address', 'inline_entity_form', 'commerce',
    'commerce_price', 'commerce_store', 'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);
    $this->container->get('commerce_price.currency_importer')->import('USD');

    $attribute_set = $this->createAttributeSet('default', 'color', [
      'cyan' => 'Cyan',
      'yellow' => 'Yellow',
    ]);

    $this->productDefaultDisplay = commerce_get_entity_display('commerce_product', 'default', 'view');
    $this->attributeDefaultDisplay = commerce_get_entity_display('commerce_product_attribute_value', 'color', 'view');
    $this->productViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');

    EntityViewMode::create([
      'id' => 'commerce_product.catalog',
      'label' => 'Catalog',
      'targetEntityType' => 'commerce_product',
    ])->save();

    $this->container->get('router.builder')->rebuildIfNeeded();

    $this->attributeDefaultDisplay->setComponent('name', [
      'label' => 'above',
    ]);
    $this->attributeDefaultDisplay->save();

    $variations = $this->createProductVariations('default', [
      ['price' => 999, 'attribute_color' => $attribute_set['cyan']],
      ['price' => 999, 'attribute_color' => $attribute_set['yellow']],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => $variations,
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
