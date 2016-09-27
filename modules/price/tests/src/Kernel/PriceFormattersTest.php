<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests price formatters provided by Price module.
 *
 * @group commerce
 */
class PriceFormattersTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   *
   * @todo should commerce_test provide a simplistic PurchasableEntity?
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path', 'text',
    'entity', 'views', 'address', 'inline_entity_form', 'commerce',
    'commerce_price', 'commerce_store', 'commerce_product',
    'commerce_price_test',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation1;

  /**
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation2;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $productVariationDefaultDisplay;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $productVariationViewBuilder;

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
    $this->productVariationDefaultDisplay = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $this->productVariationViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product_variation');

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();
    $this->variation1 = $variation;

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();
    $this->variation2 = $variation;
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_default',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation1->price, 'default');
    $this->render($build);

    $this->assertText('$12.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation2->price, 'default');
    $this->render($build);

    $this->assertText('$12.00');
  }

  /**
   * Tests the calculated price formatter.
   */
  public function testCalculatedFormatter() {
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_calculated',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation1->price, 'default');
    $this->render($build);

    $this->assertText('$12.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation2->price, 'default');
    $this->render($build);

    $this->assertText('$9.00');
  }

}
