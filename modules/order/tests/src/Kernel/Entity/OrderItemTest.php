<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the order item entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\OrderItem
 *
 * @group commerce
 */
class OrderItemTest extends OrderKernelTestBase {

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
   * @covers ::getTotalPrice
   * @covers ::recalculateTotalPrice
   * @covers ::getAdjustments
   * @covers ::setAdjustments
   * @covers ::addAdjustment
   * @covers ::removeAdjustment
   * @covers ::getAdjustedTotalPrice
   * @covers ::getAdjustedUnitPrice
   * @covers ::getData
   * @covers ::setData
   * @covers ::unsetData
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
      'type' => 'fee',
      'label' => 'Random fee',
      'amount' => new Price('2.00', 'USD'),
    ]);
    $order_item->addAdjustment($adjustments[0]);
    $order_item->addAdjustment($adjustments[1]);
    $adjustments = $order_item->getAdjustments();
    $this->assertEquals($adjustments, $order_item->getAdjustments());
    $this->assertEquals($adjustments, $order_item->getAdjustments(['custom', 'fee']));
    $this->assertEquals([$adjustments[0]], $order_item->getAdjustments(['custom']));
    $this->assertEquals([$adjustments[1]], $order_item->getAdjustments(['fee']));
    $order_item->removeAdjustment($adjustments[0]);
    $this->assertEquals([$adjustments[1]], $order_item->getAdjustments());
    $this->assertEquals(new Price('21.98', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(new Price('10.99', 'USD'), $order_item->getAdjustedUnitPrice());
    $order_item->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $order_item->getAdjustments());
    $this->assertEquals(new Price('9.99', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('19.98', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('20.98', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(new Price('18.98', 'USD'), $order_item->getAdjustedTotalPrice(['custom']));
    $this->assertEquals(new Price('21.98', 'USD'), $order_item->getAdjustedTotalPrice(['fee']));
    // The adjusted unit prices are the adjusted total prices divided by 2.
    $this->assertEquals(new Price('10.49', 'USD'), $order_item->getAdjustedUnitPrice());
    $this->assertEquals(new Price('9.49', 'USD'), $order_item->getAdjustedUnitPrice(['custom']));
    $this->assertEquals(new Price('10.99', 'USD'), $order_item->getAdjustedUnitPrice(['fee']));

    $this->assertEquals('default', $order_item->getData('test', 'default'));
    $order_item->setData('test', 'value');
    $this->assertEquals('value', $order_item->getData('test', 'default'));
    $order_item->unsetData('test');
    $this->assertNull($order_item->getData('test'));
    $this->assertEquals('default', $order_item->getData('test', 'default'));

    $order_item->setCreatedTime(635879700);
    $this->assertEquals(635879700, $order_item->getCreatedTime());
  }

  /**
   * Tests the legacy adjustments handling.
   *
   * @covers ::usesLegacyAdjustments
   * @covers ::getAdjustedTotalPrice
   * @covers ::getAdjustedUnitPrice
   */
  public function testHandlingLegacyAdjustments() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'My order item',
      'quantity' => '2',
      'unit_price' => new Price('9.99', 'USD'),
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => '10% off',
          'amount' => new Price('-1.00', 'USD'),
          'percentage' => '0.1',
        ]),
        new Adjustment([
          'type' => 'fee',
          'label' => 'Random fee',
          'amount' => new Price('2.00', 'USD'),
        ]),
      ],
      'uses_legacy_adjustments' => TRUE,
    ]);
    $order_item->save();

    $this->assertEquals(new Price('9.99', 'USD'), $order_item->getUnitPrice());
    $this->assertEquals(new Price('19.98', 'USD'), $order_item->getTotalPrice());
    $this->assertEquals(new Price('10.99', 'USD'), $order_item->getAdjustedUnitPrice());
    $this->assertEquals(new Price('8.99', 'USD'), $order_item->getAdjustedUnitPrice(['custom']));
    $this->assertEquals(new Price('11.99', 'USD'), $order_item->getAdjustedUnitPrice(['fee']));
    // The adjusted total prices are the adjusted unit prices multiplied by 2.
    $this->assertEquals(new Price('21.98', 'USD'), $order_item->getAdjustedTotalPrice());
    $this->assertEquals(new Price('17.98', 'USD'), $order_item->getAdjustedTotalPrice(['custom']));
    $this->assertEquals(new Price('23.98', 'USD'), $order_item->getAdjustedTotalPrice(['fee']));
  }

  /**
   * Tests the handling of invalid bundles.
   *
   * @covers ::bundleFieldDefinitions
   */
  public function testInvalidBundle() {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Could not load the "invalid" order item type.');

    $order_item = OrderItem::create([
      'type' => 'invalid',
      'title' => 'My order item',
      'quantity' => '2',
      'unit_price' => new Price('9.99', 'USD'),
    ]);
  }

}
