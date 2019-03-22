<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the formatted price data type for price fields.
 *
 * @group commerce
 */
class FormattedPriceTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_price_test',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->import('EUR');
  }

  /**
   * Test the data type through the price field property.
   */
  public function testFormattedPrice() {
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
      'sku' => strtolower($this->randomMachineName()),
      'price' => new Price('13.00', 'EUR'),
    ]);
    $variation2->save();

    $price_item = $variation1->get('price')->first();
    $this->assertInstanceOf(PriceItem::class, $price_item);
    $this->assertEquals('$12.00', $price_item->get('formatted')->getValue());

    $price_item = $variation2->get('price')->first();
    $this->assertInstanceOf(PriceItem::class, $price_item);
    $this->assertEquals('€13.00', $price_item->get('formatted')->getValue());
  }

}
