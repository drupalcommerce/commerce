<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the 'commerce_plugin_item' field type.
 *
 * @group commerce
 */
class PluginItemTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Role::create(['id' => 'test_role', 'name' => $this->randomString()])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_conditions',
      'entity_type' => 'entity_test',
      'type' => 'commerce_plugin_item:condition',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_conditions',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_actions',
      'entity_type' => 'entity_test',
      'type' => 'commerce_plugin_item:action',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_actions',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();
  }

  /**
   * Tests the condition derivative of the exectuable plugin item field.
   */
  public function testConditionFieldDerivative() {
    $test_user1 = $this->createUser([
      'name' => 'Test user 1',
      'status' => TRUE,
      'roles' => ['test_role'],
    ]);
    $test_user2 = $this->createUser([
      'name' => 'Test user 2',
      'status' => TRUE,
      'roles' => [],
    ]);

    $entity = EntityTest::create([
      'test_conditions' => [
        [
          'target_plugin_id' => 'commerce_test_user_role',
          'target_plugin_configuration' => [
            'roles' => ['test_role'],
          ],
        ],
      ],
    ]);
    $entity->save();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition_field */
    $condition_field = $entity->test_conditions->first();

    // Executes and returns TRUE that user1 has role.
    $user1_context = new Context(new ContextDefinition('entity:user'), $test_user1);
    $this->assertTrue($condition_field->getTargetInstance(['user' => $user1_context])->execute());

    // Execute and returns FALSE that user2 does not have the role.
    $user2_context = new Context(new ContextDefinition('entity:user'), $test_user2);
    $this->assertFalse($condition_field->getTargetInstance(['user' => $user2_context])->execute());
  }

  /**
   * Tests the action derivative of the exectuable plugin item field.
   */
  public function testActionFieldDerivative() {
    $entity = EntityTest::create([
      'test_actions' => [
        [
          'target_plugin_id' => 'commerce_test_throw_exception',
          'target_plugin_configuration' => [],
        ],
      ],
    ]);
    $entity->save();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition_field */
    $condition_field = $entity->test_actions->first();

    $this->setExpectedException(\Exception::class, 'Test exception action.');
    $condition_field->getTargetInstance()->execute();
  }

}
