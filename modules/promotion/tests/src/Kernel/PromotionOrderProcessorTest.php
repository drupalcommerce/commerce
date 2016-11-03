<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the promotion order processor.
 *
 * @group commerce
 */
class PromotionOrderProcessorTest extends EntityKernelTestBase {

  use StoreCreationTrait;

  /**
   * The default store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options', 'views', 'profile', 'entity_reference_revisions', 'entity',
    'commerce', 'commerce_price', 'address', 'commerce_order', 'commerce_store',
    'commerce_store', 'commerce_product', 'inline_entity_form',
    'commerce_promotion', 'state_machine', 'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_type');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_store',
      'commerce_promotion',
    ]);
    $this->store = $this->createStore(NULL, NULL, 'default', TRUE);

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
   * Tests the order amount condition.
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
    $this->order->save();

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
            'amount' => new Price('20.00', 'USD'),
          ],
        ],
      ],
    ]);
    $promotion->save();

    $this->assertTrue($promotion->applies($this->order));
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);

    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());
  }

}
