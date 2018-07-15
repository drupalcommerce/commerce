<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the "Buy X Get Y" offer.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\BuyXGetY
 *
 * @group commerce
 */
class BuyXGetYTest extends CommerceKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The test promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * The test variations.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface[]
   */
  protected $variations = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'path',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_product',
      'commerce_promotion',
    ]);
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);

    $product_type = ProductType::create([
      'id' => 'test',
      'label' => 'Test',
      'variationType' => 'default',
    ]);
    $product_type->save();
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);

    for ($i = 0; $i < 4; $i++) {
      $this->variations[$i] = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => Calculator::multiply('10', $i + 1),
          'currency_code' => 'USD',
        ],
      ]);
      $this->variations[$i]->save();
    }

    $first_product = Product::create([
      'type' => 'test',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variations[0]],
    ]);
    $first_product->save();
    $second_product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variations[1]],
    ]);
    $second_product->save();
    $third_product = Product::create([
      'type' => 'default',
      'title' => 'Hat 1',
      'stores' => [$this->store],
      'variations' => [$this->variations[2]],
    ]);
    $third_product->save();
    $fourth_product = Product::create([
      'type' => 'default',
      'title' => 'Hat 2',
      'stores' => [$this->store],
      'variations' => [$this->variations[3]],
    ]);
    $fourth_product->save();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'uid' => $this->createUser(),
      'store_id' => $this->store,
      'order_items' => [],
    ]);

    // Buy 6 "test" products, get 4 hats.
    $this->promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_buy_x_get_y',
        'target_plugin_configuration' => [
          'buy_quantity' => 6,
          'buy_conditions' => [
            [
              'plugin' => 'order_item_product_type',
              'configuration' => [
                'product_types' => ['test'],
              ],
            ],
          ],
          'get_quantity' => 4,
          'get_conditions' => [
            [
              'plugin' => 'order_item_product',
              'configuration' => [
                'products' => [
                  ['product' => $third_product->uuid()],
                  ['product' => $fourth_product->uuid()],
                ],
              ],
            ],
          ],
          'offer_type' => 'fixed_amount',
          'offer_amount' => [
            'number' => '1.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'status' => FALSE,
    ]);
  }

  /**
   * Tests the non-applicable use cases.
   *
   * @covers ::apply
   */
  public function testNotApplicable() {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '2',
    ]);
    $first_order_item->save();
    $second_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[1], [
      'quantity' => '4',
    ]);
    $second_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item]);
    $this->order->save();

    // Insufficient purchase quantity.
    // Only the first order item is counted (due to the product type condition),
    // and its quantity is too small (2 < 6).
    $this->promotion->apply($this->order);
    $this->assertEmpty($this->order->collectAdjustments());

    // Sufficient purchase quantity, but no offer order item found.
    $first_order_item->setQuantity(6);
    $first_order_item->save();
    $this->order->save();
    $this->promotion->apply($this->order);
    $this->assertEmpty($this->order->collectAdjustments());
  }

  /**
   * Tests the fixed amount off offer type.
   *
   * @covers ::apply
   */
  public function testFixedAmountOff() {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '7',
    ]);
    $first_order_item->save();
    $second_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[1], [
      'quantity' => '2',
    ]);
    // Test having a single offer order item, quantity < get_quantity.
    $third_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '3',
    ]);
    $second_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item, $third_order_item]);
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());

    $adjustments = $third_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-3', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());

    // Test having two offer order items, one ($third_order_item) reduced
    // completely, the other ($fourth_order_item) reduced partially.
    $fourth_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '2',
    ]);
    $this->order->addItem($fourth_order_item);
    $this->order->clearAdjustments();
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item, $fourth_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());
    $this->assertCount(1, $fourth_order_item->getAdjustments());

    $adjustments = $third_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-3', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());

    $adjustments = $fourth_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-1', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());
  }

  /**
   * Tests the percentage off offer type.
   *
   * @covers ::apply
   */
  public function testPercentageOff() {
    $offer = $this->promotion->getOffer();
    $offer_configuration = $offer->getConfiguration();
    $offer_configuration['offer_type'] = 'percentage';
    $offer_configuration['offer_percentage'] = '0.1';
    $offer_configuration['offer_amount'] = NULL;
    $offer->setConfiguration($offer_configuration);
    $this->promotion->setOffer($offer);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      // Double the buy_quantity -> double the get_quantity.
      'quantity' => '13',
    ]);
    $first_order_item->save();
    $second_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[1], [
      'quantity' => '2',
    ]);
    // Test having a single offer order item, quantity < get_quantity.
    $third_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '6',
    ]);
    $second_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item, $third_order_item]);
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());

    $adjustments = $third_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-18', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());

    // Test having two offer order items, one ($third_order_item) reduced
    // completely, the other ($fourth_order_item) reduced partially.
    $fourth_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '3',
    ]);
    $this->order->addItem($fourth_order_item);
    $this->order->clearAdjustments();
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item, $fourth_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());
    $this->assertCount(1, $fourth_order_item->getAdjustments());

    $adjustments = $third_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-18', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());

    $adjustments = $fourth_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-6', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());
  }

  /**
   * Tests the same order item matching both buy and get conditions.
   *
   * @covers ::apply
   */
  public function testSameOrderItem() {
    $offer = $this->promotion->getOffer();
    $offer_configuration = $offer->getConfiguration();
    $offer_configuration['buy_quantity'] = '1';
    $offer_configuration['buy_conditions'] = [];
    $offer_configuration['get_quantity'] = '1';
    $offer_configuration['get_conditions'] = [];
    $offer->setConfiguration($offer_configuration);
    $this->promotion->setOffer($offer);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    // '2' buy quantities, '2' get quantities, '1' ignored/irrelevant quantity.
    $order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '5',
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->save();
    $this->promotion->apply($this->order);
    list($order_item) = $this->order->getItems();

    $this->assertCount(1, $order_item->getAdjustments());
    $adjustments = $order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-2', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());
  }

  /**
   * Tests order item sorting.
   *
   * @covers ::apply
   */
  public function testOrderItemSorting() {
    // First cheapest product gets 50% off.
    $offer = $this->promotion->getOffer();
    $offer_configuration = $offer->getConfiguration();
    $offer_configuration['get_quantity'] = '1';
    $offer_configuration['offer_type'] = 'percentage';
    $offer_configuration['offer_percentage'] = '0.5';
    $offer_configuration['offer_amount'] = NULL;
    $offer->setConfiguration($offer_configuration);
    $this->promotion->setOffer($offer);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '6',
    ]);
    $first_order_item->save();
    // Both order items match the get_conditions, $third_order_item should be
    // discounted because it is cheaper.
    $second_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[3], [
      'quantity' => '1',
    ]);
    $second_order_item->save();
    $third_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '1',
    ]);
    $third_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item, $third_order_item]);
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());
  }

  /**
   * Tests working with decimal quantities.
   *
   * @covers ::apply
   */
  public function testDecimalQuantities() {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '2.5',
    ]);
    $first_order_item->save();
    $second_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[0], [
      'quantity' => '3.5',
    ]);
    $second_order_item->save();
    $third_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '1.5',
    ]);
    $third_order_item->save();
    $fourth_order_item = $order_item_storage->createFromPurchasableEntity($this->variations[2], [
      'quantity' => '5.5',
    ]);
    $fourth_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item, $third_order_item, $fourth_order_item]);
    $this->order->save();
    $this->promotion->apply($this->order);
    list($first_order_item, $second_order_item, $third_order_item, $fourth_order_item) = $this->order->getItems();

    $this->assertCount(0, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
    $this->assertCount(1, $third_order_item->getAdjustments());
    $this->assertCount(1, $fourth_order_item->getAdjustments());

    $adjustments = $third_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-1.5', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());

    $adjustments = $fourth_order_item->getAdjustments();
    $adjustment = reset($adjustments);
    $this->assertEquals('promotion', $adjustment->getType());
    $this->assertEquals(new Price('-2.5', 'USD'), $adjustment->getAmount());
    $this->assertEquals($this->promotion->id(), $adjustment->getSourceId());;
  }

}
