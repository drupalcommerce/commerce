<?php

namespace Drupal\Tests\commerce\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the plugin select widgets.
 *
 * @group commerce
 */
class PluginSelectTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The entity_test storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityTestStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'text',
    'filter',
    'entity_test',
    'commerce_test',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer entity_test content',
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
      'type' => 'commerce_plugin_item:commerce_condition',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_conditions',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $this->entityTestStorage = $this->container->get('entity_type.manager')->getStorage('entity_test');
  }

  /**
   * Tests the plugin_select widget.
   */
  public function testPluginSelect() {
    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_conditions', [
      'type' => 'commerce_plugin_select',
    ])->save();

    $this->doTest();
  }

  /**
   * Tests the plugin_radios widget.
   */
  public function testPluginRadios() {
    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_conditions', [
      'type' => 'commerce_plugin_radios',
    ])->save();

    $this->doTest();
  }

  /**
   * Performs the assertions common to both test methods.
   */
  protected function doTest() {
    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
    ]);
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->getSession()->getPage()->fillField('test_conditions[0][target_plugin_id]', 'order_item_quantity');
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'test_conditions[0][target_plugin_configuration][order_item_quantity][operator]' => '==',
      'test_conditions[0][target_plugin_configuration][order_item_quantity][quantity]' => '99',
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $this->entityTestStorage->resetCache([$entity->id()]);
    $entity = $this->entityTestStorage->load($entity->id());
    $this->assertEquals([
      'operator' => '==',
      'quantity' => 99,
    ], $entity->test_conditions->target_plugin_configuration);

    // Select the other condition.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->getSession()->getPage()->fillField('test_conditions[0][target_plugin_id]', 'order_total_price');
    $this->waitForAjaxToFinish();

    $this->submitForm([
      'test_conditions[0][target_plugin_configuration][order_total_price][operator]' => '<',
      'test_conditions[0][target_plugin_configuration][order_total_price][amount][number]' => '6.67',
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $this->entityTestStorage->resetCache([$entity->id()]);
    $entity = $this->entityTestStorage->load($entity->id());
    $this->assertEquals([
      'operator' => '<',
      'amount' => [
        'number' => '6.67',
        'currency_code' => 'USD',
      ],
    ], $entity->test_conditions->target_plugin_configuration);
  }

}
