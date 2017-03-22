<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the promotion conditions provided by commerce_product
 *
 * @group commerce
 * @group commerce_product
 * @group commerce_promotion
 */
class PromotionConditionsTest extends CommerceKernelTestBase {

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
   * The variation to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'entity_reference_revisions',
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

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installConfig([
      'commerce_product',
      'profile',
      'commerce_order',
      'commerce_promotion',
    ]);

    $this->order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'order_items' => [],
    ]);

    $this->variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => '9.99',
        'currency_code' => 'USD',
      ],
    ]);
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation],
    ]);
    $product->save();
    $this->reloadEntity($this->variation);
    $this->variation->product_id = $product->id();
    $this->variation->save();
  }

  public function testProductEquals() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 1,
      'purchased_entity' => $this->variation->id(),
      'unit_price' => $this->variation->getPrice(),
    ]);
    $order_item->save();
    $this->order->addItem($order_item);

    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_item_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_product_equals',
          'target_plugin_configuration' => [
            'product_id' => $this->variation->getProductId(),
          ],
        ],
      ],
    ]);
    $promotion->save();

    $this->assertTrue($promotion->applies($order_item));

    $new_product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $new_product->save();

    $promotion2 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_item_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'commerce_promotion_product_equals',
          'target_plugin_configuration' => [
            'product_id' => $new_product->id(),
          ],
        ],
      ],
    ]);
    $promotion2->save();

    $this->assertFalse($promotion2->applies($order_item));
  }

}
