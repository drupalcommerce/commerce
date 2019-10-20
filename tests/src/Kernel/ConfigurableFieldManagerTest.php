<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\entity\BundleFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the ConfigurableFieldManager class.
 *
 * @coversDefaultClass \Drupal\commerce\ConfigurableFieldManager
 *
 * @group commerce
 */
class ConfigurableFieldManagerTest extends CommerceKernelTestBase {

  /**
   * The configurable field manager.
   *
   * @var \Drupal\commerce\ConfigurableFieldManagerInterface
   */
  protected $configurableFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configurableFieldManager = $this->container->get('commerce.configurable_field_manager');
  }

  /**
   * @covers ::createField
   * @covers ::deleteField
   * @covers ::hasData
   */
  public function testManager() {
    $field_definition = BundleFieldDefinition::create('entity_reference')
      ->setTargetEntityTypeId('entity_test')
      ->setTargetBundle('entity_test')
      ->setName('stores')
      ->setLabel('Stores')
      ->setCardinality(BundleFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => -10,
      ]);
    $this->configurableFieldManager->createField($field_definition);

    // Confirm that the field was created with the specified options.
    $entity_test = EntityTest::create();
    $this->assertNotEmpty($entity_test->hasField('stores'));
    $created_definition = $entity_test->getFieldDefinition('stores');
    $this->assertInstanceOf(FieldConfig::class, $created_definition);
    $this->assertEquals($created_definition->getLabel(), $field_definition->getLabel());
    $this->assertEquals($created_definition->isRequired(), $field_definition->isRequired());
    $this->assertEquals($created_definition->isTranslatable(), $field_definition->isTranslatable());
    $this->assertEquals('commerce_store', $field_definition->getSetting('target_type'));
    $created_storage_definition = $created_definition->getFieldStorageDefinition();
    $this->assertEquals($created_storage_definition->getCardinality(), $field_definition->getCardinality());

    // Confirm that a form display was created and populated with the options.
    $form_display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $component = $form_display->getComponent('stores');
    $this->assertEquals('commerce_entity_select', $component['type']);
    $this->assertEquals('-10', $component['weight']);

    // Confirm that the field is functional.
    $entity_test->get('stores')->appendItem($this->store);
    $entity_test->save();
    $entity_test = $this->reloadEntity($entity_test);
    $this->assertEquals($this->store->id(), $entity_test->stores->target_id);
    $this->assertNotEmpty($this->configurableFieldManager->hasData($field_definition));
    $entity_test->delete();
    $this->assertEmpty($this->configurableFieldManager->hasData($field_definition));

    // Delete the field.
    $this->configurableFieldManager->deleteField($field_definition);
    $entity_test = EntityTest::create();
    $this->assertEmpty($entity_test->hasField('stores'));
  }

  /**
   * Tests passing an invalid field definition.
   */
  public function testInvalidDefinition() {
    $field_definition = BundleFieldDefinition::create('entity_reference');
    $field_definition->setName('stores');
    $this->expectException(\InvalidArgumentException::class);
    $this->configurableFieldManager->createField($field_definition);
  }

  /**
   * Tests trying to delete an unknown field.
   */
  public function testInvalidDelete() {
    $field_definition = BundleFieldDefinition::create('entity_reference')
      ->setTargetEntityTypeId('entity_test')
      ->setTargetBundle('entity_test')
      ->setName('stores');
    $expected_message = 'The field "stores" does not exist on bundle "entity_test" of entity type "entity_test".';
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($expected_message);
    $this->configurableFieldManager->deleteField($field_definition);
  }

}
