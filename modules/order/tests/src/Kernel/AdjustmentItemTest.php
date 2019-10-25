<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the adjustment field.
 *
 * @group commerce
 */
class AdjustmentItemTest extends OrderKernelTestBase {

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_adjustments',
      'entity_type' => 'entity_test',
      'type' => 'commerce_adjustment',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_adjustments',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $entity = EntityTest::create([
      'name' => 'Test',
    ]);
    $entity->save();
    $this->testEntity = $entity;
  }

  /**
   * Tests the adjustment item field defined by an array.
   */
  public function testAdjustmentItem() {
    /** @var \Drupal\Core\Field\FieldItemListInterface $adjustment_item_list */
    $adjustment_item_list = $this->testEntity->test_adjustments;
    $adjustment_item_list->appendItem(new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
    ]));

    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = $adjustment_item_list->first()->value;
    $this->assertEquals('custom', $adjustment->getType());
    $this->assertEquals('10% off', $adjustment->getLabel());
    $this->assertEquals('-1.00', $adjustment->getAmount()->getNumber());
    $this->assertEquals('USD', $adjustment->getAmount()->getCurrencyCode());
    $this->assertEquals('1', $adjustment->getSourceId());
  }

}
