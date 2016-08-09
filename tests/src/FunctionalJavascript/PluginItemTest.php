<?php

namespace Drupal\Tests\commerce\FunctionalJavascript;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the plugin select widget.
 *
 * @group commerce
 */
class PluginItemTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;
  use StoreCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user', 'system', 'field', 'text', 'filter', 'entity_test', 'field_ui',
    'commerce', 'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer entity_test content',
      'administer entity_test fields',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    Role::create(['id' => 'test_role', 'label' => $this->randomString()])->save();
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

    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_conditions', [
      'type' => 'commerce_plugin_select',
    ])->save();
  }

  /**
   * Tests the plugin select field.
   */
  public function testPluginSelectField() {
    // Add the field.
    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
    ]);
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->getSession()->getPage()->fillField('test_conditions[0][target_plugin_id]', 'commerce_test_user_role');
    $this->getSession()->wait(4000);
    $this->submitForm([
      'test_conditions[0][target_plugin_configuration][roles][test_role]' => 1,
      'test_conditions[0][target_plugin_configuration][negate]' => 0,
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    \Drupal::entityTypeManager()->getStorage('entity_test')->resetCache();
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->load($entity->id());

    $test_user1 = $this->createEntity('user', [
      'name' => 'Test user 1',
      'status' => TRUE,
      'roles' => ['test_role'],
    ]);

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition_field */
    $condition_field = $entity->test_conditions->first();
    $this->assertNotNull($condition_field);

    // Executes and returns TRUE that user1 has role.
    $user1_context = new Context(new ContextDefinition('entity:user'), $test_user1);
    $this->assertTrue($condition_field->getTargetInstance(['user' => $user1_context])->execute());
  }

}
