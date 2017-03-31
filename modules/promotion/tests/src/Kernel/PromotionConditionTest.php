<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
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
   * The condition manager.
   *
   * @var \Drupal\commerce_promotion\PromotionConditionManager
   */
  protected $conditionManager;

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
    'path',
    'profile',
    'state_machine',
    'commerce_order',
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
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
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

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'order_items' => [],
    ]);
  }

  /**
   * Tests the order total condition.
   */
  public function testOrderTotal() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '20.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $promotion->save();

    $result = $promotion->applies($this->order);
    $this->assertNotEmpty($result);

    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '50.00',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);
    $promotion->save();

    $result = $promotion->applies($this->order);

    $this->assertEmpty($result);
  }

  /**
   * Tests the order product condition.
   */
  public function testOrderProduct() {
    $product1 = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product1->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => 'Testing product',
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();

    $product1->addVariation($variation1)->save();

    $product2 = Product::create([
      'type' => 'default',
      'title' => 'Default testing product 2',
    ]);
    $product2->save();

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => 'Testing product',
      'status' => 1,
      'price' => new Price('22.00', 'USD'),
    ]);
    $variation2->save();

    $product2->addVariation($variation2)->save();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_order_item');

    // Add order item.
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();
    $this->order->addItem($order_item1);
    $this->order->save();
    $this->order = $this->reloadEntity($this->order);

    // Test if production variation2 is not in order.
    $promotion1 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_product',
          'target_plugin_configuration' => [
            'products' => [
              [
                'target_id' => $variation2->getProductId(),
              ],
            ],
          ],
        ],
      ],
    ]);
    $promotion1->save();

    $this->assertEmpty($promotion1->applies($this->order));

    // Add an another item.
    $order_item2 = $order_item_storage->createFromPurchasableEntity($variation2);
    $order_item2->save();
    $this->order->addItem($order_item2);
    $this->order->save();
    $this->order = $this->reloadEntity($this->order);

    // Test if both products variations are in order.
    $promotion2 = Promotion::create([
      'name' => 'Promotion 2',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_order_product',
          'target_plugin_configuration' => [
            'products' => [
              [
                'target_id' => $variation1->getProductId(),
              ],
              [
                'target_id' => $variation2->getProductId(),
              ],
            ],
          ],
        ],
      ],
    ]);
    $promotion2->save();

    $this->assertNotEmpty($promotion2->applies($this->order));
  }

}
