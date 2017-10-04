<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the order item entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\OrderItem
 *
 * @group commerce
 */
class OrderItemTest extends CommerceKernelTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig('commerce_order');

    // An order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests the order item entity and its methods.
   *
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getUnitPrice
   * @covers ::setUnitPrice
   * @covers ::isUnitPriceOverridden
   * @covers ::getAdjustedUnitPrice
   * @covers ::getAdjustments
   * @covers ::setAdjustments
   * @covers ::addAdjustment
   * @covers ::removeAdjustment
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   * @covers ::getAdjustedTotalPrice
   * @covers ::getData
   * @covers ::setData
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   */
  public function testOrderItem() {
    $order_item = OrderItem::create([
      'type' => 'test',
    ]);
    $order_item->save();

    $order_item->setTitle('My order item');
    $this->assertEquals('My order item', $order_item->getTitle());

    $this->assertEquals(1, $order_item->getQuantity());
    $order_item->setQuantity('2');
    $this->assertEquals(2, $order_item->getQuantity());

    $this->assertEquals(NULL, $order_item->getUnitPrice());
    $this->assertFalse($order_item->isUnitPriceOverridden());
    $unit_price = new Price('9.99', 'USD');
    $order_item->setUnitPrice($unit_price, TRUE);
    $this->assertEquals($unit_price, $order_item->getUnitPrice());
    $this->assertTrue($order_item->isUnitPriceOverridden());

    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => 'Random fee',
      'amount' => new Price('2.00', 'USD'),
    ]);
    $order_item->addAdjustment($adjustments[0]);
    $order_item->addAdjustment($adjustments[1]);
    $adjustments = $order_item->getAdjustments();
    $this->assertEquals($adjustments, $order_item->getAdjustments());
    $order_item->removeAdjustment($adjustments[0]);
    $this->assertEquals([$adjustments[1]], $order_item->getAdjustments());
    $this->assertEquals(new Price('11.99', 'USD'), $order_item->getAdjustedUnitPrice());
    $this->assertEquals(new Price('23.98', 'USD'), $order_item->getAdjustedTotalPrice());
    $order_item->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $order_item->getAdjustments());
    $this->assertEquals(new Price('10.99', 'USD'), $order_item->getAdjustedUnitPrice());
    $this->assertEquals(new Price('21.98', 'USD'), $order_item->getAdjustedTotalPrice());

    $this->assertEquals('default', $order_item->getData('test', 'default'));
    $order_item->setData('test', 'value');
    $this->assertEquals('value', $order_item->getData('test', 'default'));

    $order_item->setCreatedTime(635879700);
    $this->assertEquals(635879700, $order_item->getCreatedTime());
  }

}
