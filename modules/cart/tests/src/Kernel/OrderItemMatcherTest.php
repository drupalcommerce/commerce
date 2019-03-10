<?php

namespace Drupal\Tests\commerce_cart\Kernel;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;

/**
 * Tests the order item matcher.
 *
 * @coversDefaultClass \Drupal\commerce_cart\OrderItemMatcher
 * @group commerce
 */
class OrderItemMatcherTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * The order item matcher.
   *
   * @var \Drupal\commerce_cart\OrderItemMatcher
   */
  protected $orderItemMatcher;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation1;

  /**
   * A product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

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
    'commerce_order',
    'extra_order_item_field',
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
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_product']);
    $this->installConfig(['extra_order_item_field']);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $this->variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('1.00', 'USD'),
      'status' => 1,
    ]);

    $this->variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('2.00', 'USD'),
      'status' => 1,
    ]);

    $this->installCommerceCart();
    $this->orderItemMatcher = $this->container->get('commerce_cart.order_item_matcher');
  }

  /**
   * Tests the order item matcher.
   *
   * @covers ::matchAll
   * @covers ::match
   */
  public function testOrderItemMatcher() {
    $order_item1 = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
    ]);
    $order_item1->save();
    $order_item2 = OrderItem::create([
      'type' => 'default',
      'quantity' => 6,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
    ]);
    $order_item2->save();
    $order_item3 = OrderItem::create([
      'type' => 'default',
      'quantity' => 6,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation2,
    ]);
    $order_item3->save();

    // Order item should match just the second item.
    $matches = $this->orderItemMatcher->matchAll($order_item1, [
      $order_item2,
      $order_item3,
    ]);
    $this->assertNotEmpty($matches);
    $this->assertEquals($matches, [$order_item2]);

    // First matching item should be returned.
    $match = $this->orderItemMatcher->match($order_item1, [
      $order_item2,
      $order_item3,
    ]);
    $this->assertNotEmpty($match);
    $this->assertEquals($match, $order_item2);

    // Third order item should not match first two items.
    $matches = $this->orderItemMatcher->matchAll($order_item3, [
      $order_item1,
      $order_item2,
    ]);
    $this->assertEmpty($matches);

    // If first item doesn't match, the second should be returned.
    $match = $this->orderItemMatcher->match($order_item1, [
      $order_item3,
      $order_item2,
    ]);
    $this->assertNotEmpty($match);
    $this->assertEquals($match, $order_item2);

    // Order item with same purchased entity, different type does not match.
    $order_item4 = OrderItem::create([
      'type' => 'test',
      'quantity' => 2,
      'unit_price' => new Price('1.00', 'USD'),
      'purchased_entity' => $this->variation1,
    ]);
    $order_item4->save();

    $matches = $this->orderItemMatcher->matchAll($order_item4, [
      $order_item1,
      $order_item2,
      $order_item3,
    ]);
    $this->assertEmpty($matches);
  }

  /**
   * Tests that order items without purchased entities are not matched.
   */
  public function testNoPurchasedEntity() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order_item2 = OrderItem::create([
      'type' => 'default',
      'quantity' => 3,
      'unit_price' => new Price('1.00', 'USD'),
    ]);
    $order_item2->save();
    $match = $this->orderItemMatcher->match($order_item, [$order_item2]);
    $this->assertEmpty($match);
  }

  /**
   * Tests that order items with custom fields are matched properly.
   */
  public function testCustomField() {
    // Show field_custom_text on the add to cart form.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('commerce_order_item.default.add_to_cart');
    $this->assertNotEmpty($form_display);
    $form_display->setComponent('field_custom_text', [
      'type' => 'string_textfield',
    ]);
    $form_display->save();

    $order_item1 = OrderItem::create([
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
      'field_custom_text' => 'Blue',
    ]);
    $order_item1->save();
    $order_item2 = OrderItem::create([
      'type' => 'default',
      'quantity' => 6,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
      'field_custom_text' => 'Red',
    ]);
    $order_item2->save();
    $order_item3 = OrderItem::create([
      'type' => 'default',
      'quantity' => 4,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
      'field_custom_text' => 'Blue',
    ]);
    $order_item3->save();
    $order_item4 = OrderItem::create([
      'type' => 'default',
      'quantity' => 4,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
      'field_custom_text' => '',
    ]);
    $order_item4->save();

    // Same purchased entity, different custom text, no match.
    $matches = $this->orderItemMatcher->matchAll($order_item1, [$order_item2]);
    $this->assertEmpty($matches);

    // Same purchased entity, same custom text.
    $match = $this->orderItemMatcher->match($order_item1, [
      $order_item2,
      $order_item3,
    ]);
    $this->assertNotEmpty($match);
    $this->assertEquals($match, $order_item3);

    // Item with missing custom text, no match.
    $order_item5 = OrderItem::create([
      'type' => 'default',
      'quantity' => 5,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
    ]);
    $matches = $this->orderItemMatcher->matchAll($order_item5, [
      $order_item1,
      $order_item2,
      $order_item3,
    ]);
    $this->assertEmpty($matches);

    // Empty custom text on both sides, match.
    $order_item6 = OrderItem::create([
      'type' => 'default',
      'quantity' => 5,
      'unit_price' => new Price('12.00', 'USD'),
      'purchased_entity' => $this->variation1,
      'field_custom_text' => '',
    ]);
    $match = $this->orderItemMatcher->match($order_item6, [$order_item4]);
    $this->assertNotEmpty($match);
    $this->assertEquals($match, $order_item4);
  }

}
