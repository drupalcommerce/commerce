<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Product entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\Product
 *
 * @group commerce
 */
class ProductTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system', 'field', 'options', 'user', 'path', 'text', 'filter', 'entity',
    'entity', 'entity_test', 'address', 'views', 'inline_entity_form',
    'commerce', 'commerce_price', 'commerce_store', 'commerce_product',
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
  }

  /**
   * @covers ::getVariationIds
   * @covers ::getVariations
   * @covers ::setVariations
   * @covers ::hasVariations
   * @covers ::addVariation
   * @covers ::removeVariation
   * @covers ::hasVariation
   * @covers ::getDefaultVariation
   */
  public function testGetDefaultVariation() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation2->save();

    $product = Product::create([
      'type' => 'default',
    ]);
    $product->save();

    // An initially saved variation won't be the same as the loaded one.
    $variation1 = ProductVariation::load($variation1->id());
    $variation2 = ProductVariation::load($variation2->id());

    $variations = [$variation1, $variation2];
    $this->assertFalse($product->hasVariations());
    $product->setVariations($variations);
    $this->assertTrue($product->hasVariations());
    $variations_match = $variations == $product->getVariations();
    $this->assertTrue($variations_match);
    $variation_ids = [$variation1->id(), $variation2->id()];
    $variation_ids_match = $variation_ids == $product->getVariationIds();
    $this->assertTrue($variation_ids_match);

    $this->assertTrue($product->hasVariation($variation1));
    $product->removeVariation($variation1);
    $this->assertFalse($product->hasVariation($variation1));
    $product->addVariation($variation1);
    $this->assertTrue($product->hasVariation($variation1));

    $this->assertEquals($product->getDefaultVariation(), $variation2);
    $this->assertNotEquals($product->getDefaultVariation(), $variation1);
  }

  /**
   * Tests variation's canonical URL.
   */
  public function testCanonicalVariationLink() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation1],
    ]);
    $product->save();

    $product_url = $product->toUrl()->toString();
    $variation_url = $variation1->toUrl()->toString();
    $this->assertEquals($product_url . '?v=' . $variation1->id(), $variation_url);
  }

}
