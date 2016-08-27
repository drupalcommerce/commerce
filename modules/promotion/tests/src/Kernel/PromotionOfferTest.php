<?php

namespace Drupal\Tests\commerce_promotion\Kernel;

use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests promotion offers.
 *
 * @group commerce
 */
class PromotionOfferTest extends KernelTestBase {

  use StoreCreationTrait;

  /**
   * The default store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * The offer manager.
   *
   * @var \Drupal\commerce_promotion\PromotionOfferManager
   */
  protected $offerManager;

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
    $this->installConfig([
      'profile',
      'commerce_order',
      'commerce_store',
      'commerce_promotion',
    ]);
    $this->store = $this->createStore(NULL, NULL, 'default', TRUE);
    $this->offerManager = $this->container->get('plugin.manager.commerce_promotion_offer');

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
   * Tests order percentage off.
   */
  public function testOrderPercentageOff() {
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

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['amount']);

    $promotion->apply($this->order);

    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(36, $this->order->total_price->amount);

  }

  /**
   * Tests product percentage off.
   */
  public function testProductPercentageOff() {
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

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.50', $offer_field->target_plugin_configuration['amount']);

    $promotion->apply($line_item);

    $this->assertEquals(10, $line_item->getTotalPrice()->getDecimalAmount());
  }

}
