<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\commerce\EntityHelper;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * Tests the Entity select widget.
 *
 * @group commerce
 */
class EntitySelectWidgetTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'datetime',
  ];

  /**
   * The test stores.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface[]
   */
  protected $stores = [];

  /**
   * The stores field configuration.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $referenceField;

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'bypass node access',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'stores',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'commerce_store',
      ],
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Stores',
      'bundle' => 'article',
      'required' => TRUE,
    ]);
    $field->save();
    $display = commerce_get_entity_display('node', 'article', 'form');
    $display->setComponent('stores', [
      'type' => 'commerce_entity_select',
      'settings' => [
        'autocomplete_threshold' => 2,
      ],
    ])->save();

    $this->referenceField = $field_storage;
    $this->node = $this->createEntity('node', [
      'type' => 'article',
      'title' => $this->randomMachineName(),
    ]);
    // Set the first store.
    $this->stores[] = $this->store;
  }

  /**
   * Tests widget's hidden input type.
   */
  public function testWidget() {
    // Since the field is required, the widget should be a hidden element.
    $store_id = $this->stores[0]->id();
    $this->drupalGet($this->node->toUrl('edit-form'));
    $field = $this->getSession()->getPage()->find('xpath', '//input[@type="hidden" and @name="stores[target_id][value]" and @value="' . $store_id . '"]');
    $this->assertNotEmpty($field);

    // Create another store. The widget should now be a set of checkboxes.
    $this->createStores(1);
    $store_ids = EntityHelper::extractIds($this->stores);
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@type="checkbox" and starts-with(@name,"stores")]'));
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-1');
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-2');
    // Check store 1.
    $edit['stores[target_id][value][' . $store_ids[0] . ']'] = $store_ids[0];
    $edit['stores[target_id][value][' . $store_ids[1] . ']'] = FALSE;
    $this->submitForm($edit, t('Save'));

    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $this->node = Node::load($this->node->id());
    $node_store_ids = [];
    foreach ($this->node->get('stores') as $store_item) {
      $node_store_ids[] = $store_item->target_id;
    }
    $this->assertFieldValues($node_store_ids, [$store_ids[0]]);
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->checkboxChecked('edit-stores-target-id-value-' . $store_ids[0]);
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-' . $store_ids[1]);

    // Reduce the cardinality to 1. Checkboxes should now be radios.
    $this->referenceField->setCardinality(1)->save();
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@type="radio" and @name="stores[target_id][value]"]'));
    $this->assertSession()->checkboxChecked('edit-stores-target-id-value-' . $store_ids[0]);
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-' . $store_ids[1]);

    // Create the final store. The widget should now be an autocomplete field.
    $this->createStores(1);
    $store_labels = array_map(function ($store) {
      return $store->label() . ' (' . $store->id() . ')';
    }, $this->stores);
    $this->referenceField->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED)->save();
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@id="edit-stores-target-id-value" and starts-with(@class, "form-autocomplete")]'));
    $this->assertSession()->fieldValueEquals('stores[target_id][value]', $store_labels[0]);
    // Reference both stores 1 and 2.
    $edit = [];
    $edit['stores[target_id][value]'] = $store_labels[0] . ', ' . $store_labels[1];
    $this->submitForm($edit, t('Save'));
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $this->node = Node::load($this->node->id());
    $node_store_ids = [];
    foreach ($this->node->get('stores') as $store_item) {
      $node_store_ids[] = $store_item->target_id;
    }
    $this->assertFieldValues($node_store_ids, [$store_ids[0], $store_ids[1]]);
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('stores[target_id][value]', $store_labels[0] . ', ' . $store_labels[1]);
  }

  /**
   * Creates stores.
   *
   * @param string $num_stores
   *   The number of stores to create.
   */
  protected function createStores($num_stores) {
    for ($i = 0; $i < $num_stores; $i++) {
      $this->stores[] = $this->createStore();
    }
  }

}
