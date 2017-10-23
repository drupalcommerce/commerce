<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the purchasable entity price calculator.
 *
 * @group commerce
 */
class PurchasableEntityPriceCalculatorTest extends CommerceKernelTestBase {

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PurchasableEntityPriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_promotion',
    'commerce_order',
    'commerce_order_test',
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
          'percentage' => '0.50',
        ],
      ],
    ]);
    $promotion->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
      'stores' => [$this->store->id()],
    ]);
    $product->save();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_CALCULATED_PRICE',
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();
    $product->addVariation($variation)->save();
    $this->variation = $this->reloadEntity($variation);

    $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    $this->priceCalculator = $this->container->get('commerce_order.purchasable_entity_price_calculator');
  }

  public function testCalculation() {
    $calculated = $this->priceCalculator->calculate($this->variation, 1);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['original']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['resolved']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['calculated']);

    $calculated = $this->priceCalculator->calculate($this->variation, 1, ['promotion']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['original']);
    $this->assertEquals(new Price('6.00', 'USD'), $calculated['calculated']);

    $calculated = $this->priceCalculator->calculate($this->variation, 1, ['fee']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['original']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['calculated']);

    $calculated = $this->priceCalculator->calculate($this->variation, 1, ['test_adjustment_type']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['original']);
    $this->assertEquals(new Price('14.00', 'USD'), $calculated['calculated']);

    $calculated = $this->priceCalculator->calculate($this->variation, 1, ['promotion', 'test_adjustment_type']);
    $this->assertEquals(new Price('12.00', 'USD'), $calculated['original']);
    $this->assertEquals(new Price('8.00', 'USD'), $calculated['calculated']);
  }

}
