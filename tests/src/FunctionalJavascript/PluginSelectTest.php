<?php

namespace Drupal\Tests\commerce\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\Role;

/**
 * Tests the plugin select widgets.
 *
 * @group commerce
 */
class PluginSelectTest extends CommerceWebDriverTestBase {

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
    'commerce_payment',
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
    $this->entityTestStorage = $this->container->get('entity_type.manager')->getStorage('entity_test');
  }

  /**
   * Tests the plugin_select widget.
   */
  public function testPluginSelect() {
    $this->createField('commerce_condition');
    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_plugin', [
      'type' => 'commerce_plugin_select',
    ])->save();

    $this->doTestConditions();
  }

  /**
   * Tests the plugin_radios widget.
   */
  public function testPluginRadios() {
    $this->createField('commerce_condition');
    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_plugin', [
      'type' => 'commerce_plugin_radios',
    ])->save();

    $this->doTestConditions();
  }

  /**
   * Tests the plugin_select widget on a plugin type without configuration.
   */
  public function testPluginSelectWithoutConfiguration() {
    $this->createField('commerce_payment_method_type');
    $display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $display->setComponent('test_plugin', [
      'type' => 'commerce_plugin_select',
    ])->save();

    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
    ]);
    $this->drupalGet($entity->toUrl('edit-form'));

    $this->submitForm([
      'test_plugin[0][target_plugin_id]' => 'paypal',
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $this->entityTestStorage->resetCache([$entity->id()]);
    $entity = $this->entityTestStorage->load($entity->id());
    $this->assertEquals('paypal', $entity->test_plugin->target_plugin_id);
    $this->assertEquals([], $entity->test_plugin->target_plugin_configuration);
  }

  /**
   * Tests the configuration common to testPluginSelect and testPluginRadios.
   */
  protected function doTestConditions() {
    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
    ]);
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->getSession()->getPage()->fillField('test_plugin[0][target_plugin_id]', 'order_email');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'test_plugin[0][target_plugin_configuration][order_email][mail]' => 'test@example.com',
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $this->entityTestStorage->resetCache([$entity->id()]);
    $entity = $this->entityTestStorage->load($entity->id());
    $this->assertEquals([
      'mail' => 'test@example.com',
    ], $entity->test_plugin->target_plugin_configuration);

    // Select the other condition.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->getSession()->getPage()->fillField('test_plugin[0][target_plugin_id]', 'order_total_price');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([
      'test_plugin[0][target_plugin_configuration][order_total_price][operator]' => '<',
      'test_plugin[0][target_plugin_configuration][order_total_price][amount][number]' => '6.67',
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
    ], $entity->test_plugin->target_plugin_configuration);
  }

  /**
   * Creates a commerce_plugin_item field for the given plugin type.
   *
   * @param string $plugin_type
   *   The plugin type.
   */
  protected function createField($plugin_type) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_plugin',
      'entity_type' => 'entity_test',
      'type' => 'commerce_plugin_item:' . $plugin_type,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_plugin',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();
  }

}
