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
 * Tests promotion offers.
 *
 * @group commerce
 */
class PromotionOfferTest extends CommerceKernelTestBase {

  /**
   * The offer manager.
   *
   * @var \Drupal\commerce_promotion\PromotionOfferManager
   */
  protected $offerManager;

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
    $this->offerManager = $this->container->get('plugin.manager.commerce_promotion_offer');

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
      'uid' => $this->createUser(),
      'store_id' => $this->store,
      'order_items' => [],
    ]);
  }

  /**
   * Tests order percentage off.
   */
  public function testOrderPercentageOff() {
    // Use addOrderItem so the total is calculated.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
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
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
    ]);
    $promotion->save();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['percentage']);

    $promotion->apply($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('36.00', 'USD'), $this->order->getTotalPrice());

  }

  /**
   * Tests order fixed amount off.
   */
  public function testOrderFixedAmountOff() {
    // Starts now, enabled. No end time.
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => [$this->order->bundle()],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_fixed_amount_off',
        'target_plugin_configuration' => [
          'amount' => [
            'number' => '25.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
    ]);
    $promotion->save();

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => [
        'number' => '20.00',
        'currency_code' => 'USD',
      ],
    ]);
    $order_item->save();
    $this->order->addItem($order_item);
    $this->order->state = 'draft';
    $this->order->save();
    $this->order = $this->reloadEntity($this->order);

    // Offer amount larger than the order total.
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('-20.00', 'USD'), $this->order->getAdjustments()[0]->getAmount());
    $this->assertEquals(new Price('0.00', 'USD'), $this->order->getTotalPrice());

    // Offer amount smaller than the order total.
    $order_item->setQuantity(2);
    $order_item->save();
    $this->order->save();
    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals(1, count($this->order->getAdjustments()));
    $this->assertEquals(new Price('-25.00', 'USD'), $this->order->getAdjustments()[0]->getAmount());
    $this->assertEquals(new Price('15.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests product percentage off.
   */
  public function testProductPercentageOff() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
    ]);
    $product->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '2',
      'unit_price' => $variation->getPrice(),
      'purchased_entity' => $variation->id(),
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
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.50',
        ],
      ],
    ]);
    $promotion->save();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.50', $offer_field->target_plugin_configuration['percentage']);

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);

    $adjustments = $order_item->getAdjustments();
    $this->assertEquals(1, count($adjustments));
    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = reset($adjustments);
    // Adjustment for 50% of the order item total.
    $this->assertEquals(new Price('-5.00', 'USD'), $adjustment->getAmount());
    $this->assertEquals('0.50', $adjustment->getPercentage());
    // Adjustments don't affect total order item price, but the order's total.
    $this->assertEquals(new Price('20.00', 'USD'), $order_item->getTotalPrice());

    $this->order->recalculateTotalPrice();
    $this->assertEquals(new Price('10.00', 'USD'), $this->order->getTotalPrice());
  }

  /**
   * Tests product fixed amount off.
   */
  public function testProductFixedAmountOff() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
    ]);
    $product->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '1',
      'unit_price' => $variation->getPrice(),
      'purchased_entity' => $variation->id(),
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
        'target_plugin_id' => 'order_item_fixed_amount_off',
        'target_plugin_configuration' => [
          'amount' => [
            'number' => '15.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
    ]);
    $promotion->save();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('15.00', $offer_field->target_plugin_configuration['amount']['number']);

    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    // Offer amount larger than the order item price.
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $this->assertEquals(new Price('-10.00', 'USD'), $order_item->getAdjustments()[0]->getAmount());

    // Offer amount smaller than the order item unit price.
    $variation->setPrice(new Price('20', 'USD'));
    $variation->save();
    $this->container->get('commerce_order.order_refresh')->refresh($this->order);
    $this->order = $this->reloadEntity($this->order);
    $order_item = $this->reloadEntity($order_item);
    $this->assertEquals(1, count($order_item->getAdjustments()));
    $this->assertEquals(new Price('-15.00', 'USD'), $order_item->getAdjustments()[0]->getAmount());
  }

}
