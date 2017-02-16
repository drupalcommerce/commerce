<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the 'commerce_remote_id' field type.
 *
 * @group commerce
 */
class RemoteIdItemTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_remote_id',
      'entity_type' => 'entity_test',
      'type' => 'commerce_remote_id',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_remote_id',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();
  }

  /**
   * Tests the field.
   */
  public function testField() {
    $entity = EntityTest::create([
      'test_remote_id' => [
        'provider' => 'braintree',
        'remote_id' => '123',
      ],
    ]);
    $entity->save();
    $entity = $this->reloadEntity($entity);

    $this->assertEquals('123', $entity->test_remote_id->getByProvider('braintree'));
    $this->assertNull($entity->test_remote_id->getByProvider('stripe'));

    $entity->test_remote_id->setByProvider('braintree', '456');
    $entity->test_remote_id->setByProvider('stripe', '789');
    $entity->save();
    $entity = $this->reloadEntity($entity);

    $this->assertEquals('456', $entity->test_remote_id->getByProvider('braintree'));
    $this->assertNull($entity->test_remote_id->getByProvider('stripe'));
  }

}
