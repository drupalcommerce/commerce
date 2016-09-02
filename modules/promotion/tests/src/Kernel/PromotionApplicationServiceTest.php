<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests apply promotions.
 *
 * @group commerce
 */
class PromotionApplicationServiceTest extends EntityKernelTestBase {

  use StoreCreationTrait;

  /**
   * The promotion application service.
   *
   * @var \Drupal\commerce_promotion\PromotionApplicationServiceInterface
   */
  protected $promotionApplication;

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
  public static $modules = ['system', 'field', 'options', 'user', 'views', 'profile',
    'text', 'entity', 'commerce', 'commerce_price', 'address', 'commerce_order',
    'commerce_store', 'commerce_product', 'inline_entity_form', 'commerce_promotion',
    'state_machine', 'datetime',
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
    $this->installEntitySchema('commerce_line_item');
    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_store',
      'commerce_promotion',
    ]);

    $this->store = $this->createStore(NULL, NULL, 'default', TRUE);
    $this->promotionApplication = $this->container->get('commerce_promotion.promotion_application_service');

    // A line item type that doesn't need a purchasable entity, for simplicity.
    LineItemType::create([
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
      'line_items' => [],
    ]);
  }

  /**
   * Tests apply an order promotion.
   */
  public function testApplyOrderPromotion() {
    // Use addLineItem so the total is calculated.
    $line_item = LineItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'amount' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $line_item->save();
    $this->order->addLineItem($line_item);

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
    ]);
    $promotion->save();

    $this->promotionApplication->apply($this->order);

    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests product promotion.
   */
  public function testApplyProductPromotion() {
    // Use addLineItem so the total is calculated.
    $line_item = LineItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'amount' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $line_item->save();

    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_product_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.50',
        ],
      ],
    ]);
    $promotion->save();

    $this->order->addLineItem($line_item);
    $this->promotionApplication->apply($this->order);

    $line_item = $this->reloadEntity($line_item);
    $this->assertEquals(1, count($line_item->getAdjustments()));
    $this->assertEquals(new Price('10.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests applying a line item and order promotion.
   */
  public function testApplyTwoPromotions() {
    // Use addLineItem so the total is calculated.
    $line_item = LineItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => [
        'amount' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $line_item->save();
    $this->order->addLineItem($line_item);

    // Starts now, enabled. No end time.
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
    ]);
    $promotion1->save();
    // Starts now, enabled. No end time.
    $promotion2 = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_product_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.50',
        ],
      ],
    ]);
    $promotion2->save();

    $this->promotionApplication->apply($this->order);

    $line_item = $this->reloadEntity($line_item);
    $this->assertEquals(1, count($line_item->getAdjustments()));
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('18.00', 'USD'), $this->order->getTotalPrice());
  }

}
