<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the normalization of adjustment field.
 *
 * @group commerce
 */
class AdjustmentItemNormalizeTest extends CommerceKernelTestBase {

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'serialization',
  ];

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

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

    $this->serializer = \Drupal::service('serializer');
  }

  /**
   * Tests the normalization of adjustment field.
   */
  public function testAdjustmentItemNormalize() {
    /** @var \Drupal\Core\Field\FieldItemListInterface $adjustment_item_list */
    $adjustment_item_list = $this->testEntity->test_adjustments;
    $adjustment_item_list->appendItem(new Adjustment([
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => FALSE,
      'locked' => TRUE,
    ]));
    $expected = [
      0 => [
        'value' => [
          'type' => 'custom',
          'label' => '10% off',
          'amount' => [
            'number' => '-1.00',
            'currency_code' => 'USD',
          ],
          'percentage' => '0.1',
          'source_id' => '1',
          'included' => FALSE,
          'locked' => TRUE,
        ],
      ],
    ];
    $normalized = $this->serializer->normalize($adjustment_item_list);
    $this->assertSame($expected, $normalized);
  }

}
