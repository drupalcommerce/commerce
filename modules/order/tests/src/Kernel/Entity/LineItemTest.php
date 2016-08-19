<?php

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\commerce_price\Price;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Line item entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\LineItem
 *
 * @group commerce
 */
class LineItemTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system', 'field', 'options', 'user', 'entity',
    'views', 'address', 'profile', 'state_machine',
    'inline_entity_form', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('user');
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
   * @covers ::recalculateTotalPrice
   * @covers ::addAdjustment
   * @covers ::setAdjustments
   * @covers ::getAdjustments
   */
  public function testLineItem() {
    $line_item = LineItem::create([
      'type' => 'test',
      'unit_price' => new Price(9.99, 'USD'),
    ]);
    $line_item->save();

    $line_item->addAdjustment(new Adjustment([
      'type' => 'discount',
      'label' => '10% off',
      'amount' => new Price(-1.00, 'USD'),
      'source_id' => '1',
    ]));
    $this->assertEquals(8.99, $line_item->total_price->amount);
    $line_item->addAdjustment(new Adjustment([
      'type' => 'order_adjustment',
      'label' => 'Random fee',
      'amount' => new Price(2.00, 'USD'),
      'source_id' => '',
    ]));
    $this->assertEquals(10.99, $line_item->total_price->amount);

    $adjustments = $line_item->getAdjustments();
    $this->assertEquals(2, count($adjustments));

    foreach ($adjustments as $adjustment) {
      $line_item->removeAdjustment($adjustment);
    }
    $this->assertEquals(9.99, $line_item->total_price->amount);
    $line_item->setAdjustments($adjustments);
    $this->assertEquals(10.99, $line_item->total_price->amount);
  }

}
