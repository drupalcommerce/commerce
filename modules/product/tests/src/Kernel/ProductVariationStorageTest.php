<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the product variation storage.
 *
 * @group commerce
 */
class ProductVariationStorageTest extends EntityKernelTestBase {

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path',
    'text', 'entity', 'filter', 'entity_test', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product', 'views', 'address', 'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);

    $this->variationStorage = $this->container->get('entity_type.manager')->getStorage('commerce_product_variation');
  }

  /**
   * Tests loadEnabled() function.
   */
  public function testLoadEnabled() {
    $variations = [];
    for ($i = 1; $i <= 3; $i++) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => strtolower($this->randomMachineName()),
        'title' => $this->randomString(),
        'status' => $i % 2,
      ]);
      $variation->save();
      $variations[] = $variation;
    }
    $variations = array_reverse($variations);
    $product = Product::create([
      'type' => 'default',
      'variations' => $variations,
    ]);
    $product->save();

    $variationsFiltered = $this->variationStorage->loadEnabled($product);
    $this->assertEquals(2, count($variationsFiltered), '2 out of 3 variations are enabled');
    $this->assertEquals(reset($variations)->getSku(), reset($variationsFiltered)->getSku(), 'The sort order of the variations remains the same');
  }

}
