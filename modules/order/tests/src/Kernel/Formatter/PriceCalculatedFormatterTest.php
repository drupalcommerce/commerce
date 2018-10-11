<?php

namespace Drupal\Tests\commerce_order\Kernel\Formatter;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the calculated price formatter.
 *
 * @group commerce
 */
class PriceCalculatedFormatterTest extends CommerceKernelTestBase {

  /**
   * The first variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $firstVariation;

  /**
   * The second variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $secondVariation;

  /**
   * The commerce_product_variation view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_promotion',
    'commerce_tax',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_promotion');
    $this->installConfig(['commerce_product', 'commerce_order']);

    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.5',
        ],
      ],
    ]);
    $promotion->save();

    // The default store is US-WI, so imagine that the US has VAT.
    TaxType::create([
      'id' => 'us_vat',
      'label' => 'US VAT',
      'plugin' => 'custom',
      'configuration' => [
        'display_inclusive' => TRUE,
        'rates' => [
          [
            'id' => 'standard',
            'label' => 'Standard',
            'percentage' => '0.2',
          ],
        ],
        'territories' => [
          ['country_code' => 'US', 'administrative_area' => 'WI'],
          ['country_code' => 'US', 'administrative_area' => 'SC'],
        ],
      ],
    ])->save();

    $first_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_CALCULATED_PRICE',
      'status' => 1,
      'price' => new Price('3.00', 'USD'),
    ]);
    $first_variation->save();

    $second_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_CALCULATED_PRICE2',
      'status' => 1,
      'price' => new Price('4.00', 'USD'),
    ]);
    $second_variation->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
      'stores' => [$this->store->id()],
      'variations' => [$first_variation, $second_variation],
    ]);
    $product->save();

    $this->firstVariation = $this->reloadEntity($first_variation);
    $this->secondVariation = $this->reloadEntity($second_variation);

    $user = $this->createUser(['mail' => 'user1@example.com']);
    $this->container->get('current_user')->setAccount($user);

    $this->viewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product_variation');
  }

  /**
   * Tests the rendered output.
   */
  public function testRender() {
    $variation_display = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $variation_display->setComponent('price', [
      'label' => 'above',
      'type' => 'commerce_price_calculated',
      'settings' => [],
    ]);
    $variation_display->save();

    $variation_build = $this->viewBuilder->view($this->firstVariation);
    $this->render($variation_build);
    $this->assertEscaped('$3.00');

    $variation_build = $this->viewBuilder->view($this->secondVariation);
    $this->render($variation_build);
    $this->assertEscaped('$4.00');

    $variation_display->setComponent('price', [
      'label' => 'above',
      'type' => 'commerce_price_calculated',
      'settings' => [
        'adjustment_types' => [
          'tax' => 'tax',
        ],
      ],
    ]);
    $variation_display->save();

    $variation_build = $this->viewBuilder->view($this->firstVariation);
    $this->render($variation_build);
    $this->assertEscaped('$3.60');

    $variation_build = $this->viewBuilder->view($this->secondVariation);
    $this->render($variation_build);
    $this->assertEscaped('$4.80');

    $variation_display->setComponent('price', [
      'label' => 'above',
      'type' => 'commerce_price_calculated',
      'settings' => [
        'adjustment_types' => [
          'tax' => 'tax',
          'promotion' => 'promotion',
        ],
      ],
    ]);
    $variation_display->save();

    $variation_build = $this->viewBuilder->view($this->firstVariation);
    $this->render($variation_build);
    $this->assertEscaped('$1.80');

    $variation_build = $this->viewBuilder->view($this->secondVariation);
    $this->render($variation_build);
    $this->assertEscaped('$2.40');
  }

}
