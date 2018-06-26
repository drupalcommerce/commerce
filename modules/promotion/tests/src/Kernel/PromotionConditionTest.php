<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests promotion conditions.
 *
 * @group commerce
 */
class PromotionConditionTest extends CommerceKernelTestBase {

  /**
   * The test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

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
    'commerce_promotion_test',
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

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $user = $this->createUser();

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $user->id(),
      'order_items' => [],
    ]);
  }

  /**
   * Tests promotion conditions.
   */
  public function testPromotionConditions() {
    // Starts now, enabled. No end time. Matches orders under $20 or over $100.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'operator' => '<',
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'operator' => '>',
            'amount' => [
              'number' => '100.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'condition_operator' => 'OR',
    ]);
    $promotion->save();

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 3,
      'unit_price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $result = $promotion->applies($this->order);
    $this->assertFalse($result);

    $order_item->setQuantity(1);
    $order_item->save();
    $this->order->save();
    $result = $promotion->applies($this->order);
    $this->assertTrue($result);

    $order_item->setQuantity(11);
    $order_item->save();
    $this->order->save();
    $result = $promotion->applies($this->order);
    $this->assertTrue($result);

    // No order total can satisfy both conditions.
    $promotion->setConditionOperator('AND');
    $result = $promotion->applies($this->order);
    $this->assertFalse($result);
  }

  /**
   * Tests offer conditions.
   */
  public function testOfferConditions() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'conditions' => [
            [
              'plugin' => 'order_item_product_type',
              'configuration' => [
                'product_types' => ['default'],
              ],
            ],
          ],
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'operator' => '>',
            'amount' => [
              'number' => '30.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
      'condition_operator' => 'AND',
    ]);
    $promotion->save();

    $product_type = ProductType::create([
      'id' => 'test',
      'label' => 'Test',
      'variationType' => 'default',
    ]);
    $product_type->save();
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);

    $first_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => '20',
        'currency_code' => 'USD',
      ],
    ]);
    $first_variation->save();
    $second_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => '30',
        'currency_code' => 'USD',
      ],
    ]);
    $second_variation->save();

    $first_product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$first_variation],
    ]);
    $first_product->save();
    $second_product = Product::create([
      'type' => 'test',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$second_variation],
    ]);
    $second_product->save();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item');
    $first_order_item = $order_item_storage->createFromPurchasableEntity($first_variation);
    $first_order_item->save();
    $second_order_item = $order_item_storage->createFromPurchasableEntity($second_variation);
    $second_order_item->save();
    $this->order->setItems([$first_order_item, $second_order_item]);
    $this->order->state = 'draft';
    $this->order->save();
    $this->order = $this->reloadEntity($this->order);
    $first_order_item = $this->reloadEntity($first_order_item);
    $second_order_item = $this->reloadEntity($second_order_item);

    $this->assertCount(1, $first_order_item->getAdjustments());
    $this->assertCount(0, $second_order_item->getAdjustments());
  }

}
