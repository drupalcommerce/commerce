<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\commerce_price\Price;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the Line item entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\LineItem
 *
 * @group commerce
 */
class LineItemTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'entity',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_line_item');
    $this->installConfig('commerce_order');

    // A line item type that doesn't need a purchasable entity, for simplicity.
    LineItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests the line item entity and its methods.
   *
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getUnitPrice
   * @covers ::setUnitPrice
   * @covers ::getAdjustments
   * @covers ::setAdjustments
   * @covers ::addAdjustment
   * @covers ::removeAdjustment
   * @covers ::recalculateTotalPrice
   * @covers ::getTotalPrice
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   */
  public function testLineItem() {
    $line_item = LineItem::create([
      'type' => 'test',
    ]);
    $line_item->save();

    $line_item->setTitle('My line item');
    $this->assertEquals('My line item', $line_item->getTitle());

    $this->assertEquals(1, $line_item->getQuantity());
    $line_item->setQuantity('2');
    $this->assertEquals(2, $line_item->getQuantity());

    $this->assertEquals(NULL, $line_item->getUnitPrice());
    $unit_price = new Price('9.99', 'USD');
    $line_item->setUnitPrice($unit_price);
    $this->assertEquals($unit_price, $line_item->getUnitPrice());

    $line_item->setQuantity('1');
    $adjustments = [];
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
    ]);
    $adjustments[] = new Adjustment([
      'type' => 'custom',
      'label' => 'Random fee',
      'amount' => new Price('2.00', 'USD'),
    ]);
    $line_item->addAdjustment($adjustments[0]);
    $line_item->addAdjustment($adjustments[1]);
    $adjustments = $line_item->getAdjustments();
    $this->assertEquals($adjustments, $line_item->getAdjustments());
    $line_item->removeAdjustment($adjustments[0]);
    $this->assertEquals([$adjustments[1]], $line_item->getAdjustments());
    $line_item->setAdjustments($adjustments);
    $this->assertEquals($adjustments, $line_item->getAdjustments());

    $line_item->setCreatedTime(635879700);
    $this->assertEquals(635879700, $line_item->getCreatedTime());
  }

}
