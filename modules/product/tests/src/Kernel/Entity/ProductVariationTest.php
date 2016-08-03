<?php
/**
 * @file
 * Contains \Drupal\Tests\commerce_product\Kernel\Entity\ProductVariationTest.
 */

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Product Variation entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\ProductVariation
 *
 * @group commerce
 */
class ProductVariationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'profile',
    'state_machine',
    'commerce_order',
    'system',
    'field',
    'options',
    'user',
    'path',
    'text',
    'filter',
    'entity',
    'entity_test',
    'address',
    'views',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
  ];

  /**
   * Tests getters and setters for the product variation interface.
   *
   * @covers ::getPrice
   * @covers ::getSku
   * @covers ::setSku
   */
  public function testProductVariation() {
    // Create a variation.
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'AZAE1252',
      'title' => $this->randomString(),
      'status' => 1,
      'price' => [
        'amount' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();

    // ProductVariation: Test price get and set.
    $this->assertEquals(9.99, $variation->getPrice()->getDecimalAmount());
    $variation->set('price', [
      'amount' => 13.37,
      'currency_code' => 'USD',
    ]);
    $variation->save();
    $this->assertEquals(13.37, $variation->getPrice()->getDecimalAmount());

    // ProductVariation: Test sku get and set.
    $this->assertEquals('AZAE1252', $variation->getSku());
    $variation->setSku('ABAA9898');
    $variation->save();
    $this->assertEquals('ABAA9898', $variation->getSku());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);
    $this->container->get('commerce_price.currency_importer')->import('USD');
  }

}
