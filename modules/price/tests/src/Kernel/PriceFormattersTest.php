<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests price formatters provided by Price module.
 *
 * @group commerce
 */
class PriceFormattersTest extends CommerceKernelTestBase {

  use StoreCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   *
   * @todo should commerce_test provide a simplistic PurchasableEntity?
   */
  public static $modules = [
    'commerce_price_test',
    'commerce_product',
  ];

  /**
   * The first product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation1;

  /**
   * The second product variation.
   *
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

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $this->productVariationDefaultDisplay = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $this->productVariationViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product_variation');

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'list_price' => new Price('14.00', 'USD'),
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();
    $this->variation1 = $variation;

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'list_price' => new Price('26.00', 'USD'),
      'price' => new Price('24.00', 'USD'),
    ]);
    $variation->save();
    $this->variation2 = $variation;
  }

  /**
   * Tests the default formatter.
   */
  public function testDefaultFormatter() {
    $this->productVariationDefaultDisplay->setComponent('list_price', [
      'type' => 'commerce_price_default',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_default',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation1->list_price, 'default');
    $this->render($build);
    $this->assertText('$14.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation1->price, 'default');
    $this->render($build);
    $this->assertText('$12.00');

    $this->productVariationDefaultDisplay->setComponent('list_price', [
      'type' => 'commerce_price_default',
      'settings' => [
        'currency_display' => 'code',
      ],
    ]);
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_default',
      'settings' => [
        'currency_display' => 'code',
      ],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation2->list_price, 'default');
    $this->render($build);
    $this->assertText('USD26.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation2->price, 'default');
    $this->render($build);
    $this->assertText('USD24.00');
  }

  /**
   * Tests the calculated price formatter.
   */
  public function testCalculatedFormatter() {
    $this->productVariationDefaultDisplay->setComponent('list_price', [
      'type' => 'commerce_price_calculated',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_calculated',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation1->list_price, 'default');
    $this->render($build);
    $this->assertText('$14.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation1->price, 'default');
    $this->render($build);
    $this->assertText('$12.00');

    $this->productVariationDefaultDisplay->setComponent('list_price', [
      'type' => 'commerce_price_calculated',
      'settings' => [
        'currency_display' => 'code',
      ],
    ]);
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_calculated',
      'settings' => [
        'currency_display' => 'code',
      ],
    ]);
    $this->productVariationDefaultDisplay->save();

    $build = $this->productVariationViewBuilder->viewField($this->variation2->list_price, 'default');
    $this->render($build);
    $this->assertText('USD23.00');

    $build = $this->productVariationViewBuilder->viewField($this->variation2->price, 'default');
    $this->render($build);
    $this->assertText('USD21.00');
  }

}
