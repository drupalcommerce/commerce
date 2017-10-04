<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests that price item list `equals` works as expected.
 *
 * @group commerce
 */
class PriceEqualsTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_price_test',
    'commerce_product',
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
  }

  /**
   * Tests that 12.00 and 12 are the same.
   */
  public function testPriceItemListEquals() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation1 */
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation2 */
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'price' => new Price('12', 'USD'),
    ]);
    $variation2->save();
    $this->assertTrue($variation1->get('price')->equals($variation2->get('price')));
  }

  /**
   * Tests an expected unequal amount is not equal.
   */
  public function testPriceItemListNotEquals() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation1 */
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => new Price('13.00', 'USD'),
    ]);
    $variation1->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation2 */
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'price' => new Price('12', 'USD'),
    ]);
    $variation2->save();
    $this->assertFalse($variation1->get('price')->equals($variation2->get('price')));
  }

}
