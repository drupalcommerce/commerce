<?php

/**
 * @file
 * Contains \Drupal\commerce\Tests\EntitySelectWidgetTest.
 */

namespace Drupal\commerce\Tests;

use Drupal\commerce_product\Entity\Product;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the Entity select widget.
 *
 * @group commerce
 */
class EntitySelectWidgetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce',
    'commerce_store',
    'commerce_product',
    'field',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

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
   * The test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer products',
      'administer stores',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->referenceField = FieldStorageConfig::loadByName('commerce_product', 'stores');
    $display = EntityFormDisplay::load('commerce_product.default.default');
    $display->setComponent('stores', [
      'type' => 'entity_select',
      'settings' => [
        'autocomplete_threshold' => 2,
      ],
    ])->save();

    $variation = $this->createEntity('commerce_product_variation', [
     'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'variations' => [$variation],
    ]);
  }

  /**
   * Tests widget's hidden input type.
   */
  function testWidget() {
    $form_url = 'product/' . $this->product->id() . '/edit';
    // Create the first store. Since the field is required, the widget
    // should be a hidden element.
    $this->createStores(1);
    $store_id = $this->stores[0]->id();
    $this->drupalGet($form_url);
    $this->assertFieldByXpath('//input[@type="hidden" and @name="stores[target_id][value]" and @value="' . $store_id .'"]', NULL, 'Stores field is displayed as a hidden element.');

    // Create another store. The widget should now be a set of checkboxes.
    $this->createStores(1);
    $store_ids = array_map(function ($store) {
      return $store->id();
    }, $this->stores);
    $this->drupalGet($form_url);
    $this->assertTrue((bool) $this->xpath('//input[@type="checkbox" and starts-with(@name,"stores")]'), 'Stores field is displayed as a checkboxes element.');
    $this->assertNoFieldChecked('edit-stores-target-id-value-1');
    $this->assertNoFieldChecked('edit-stores-target-id-value-2');
    // Check store 1.
    $edit['stores[target_id][value][' . $store_ids[0] .']'] = $store_ids[0];
    $edit['stores[target_id][value][' . $store_ids[1] .']'] = FALSE;
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertResponse(200);
    \Drupal::entityManager()->getStorage('commerce_product')->resetCache();
    $this->product = Product::load($this->product->id());
    $this->assertFieldValues($this->product->getStoreIds(), [$store_ids[0]], 'The correct store has been set on the product.');
    $this->drupalGet($form_url);
    $this->assertFieldChecked('edit-stores-target-id-value-' . $store_ids[0]);
    $this->assertNoFieldChecked('edit-stores-target-id-value-' . $store_ids[1]);

    // Reduce the cardinality to 1. Checkboxes should now be radios.
    $this->referenceField->setCardinality(1)->save();
    $this->drupalGet($form_url);
    $this->assertTrue((bool) $this->xpath('//input[@type="radio" and @name="stores[target_id][value]"]'), 'Stores field is displayed as a radio element.');
    $this->assertFieldChecked('edit-stores-target-id-value-' . $store_ids[0], 'Radio field for store ' . $store_ids[0] . ' is checked.');
    $this->assertNoFieldChecked('edit-stores-target-id-value-' . $store_ids[1], 'Radio field for store ' . $store_ids[1] . ' is unchecked.');

    // Create the final store. The widget should now be an autocomplete field.
    $this->createStores(1);
    $store_labels = array_map(function ($store) {
      return $store->label() . ' (' . $store->id() . ')';
    }, $this->stores);
    $this->referenceField->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED)->save();
    $this->drupalGet($form_url);
    $this->assertTrue((bool) $this->xpath('//input[@id="edit-stores-target-id-value" and starts-with(@class, "form-autocomplete")]'), 'Stores field is displayed as an autocomplete element.');
    $this->assertFieldByName('stores[target_id][value]', $store_labels[0]);
    // Reference both stores 1 and 2.
    $edit = [];
    $edit['stores[target_id][value]'] = $store_labels[0] . ', ' . $store_labels[1];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertResponse(200);
    \Drupal::entityManager()->getStorage('commerce_product')->resetCache();
    $this->product = Product::load($this->product->id());
    $this->assertFieldValues($this->product->getStoreIds(), [$store_ids[0], $store_ids[1]], 'The correct stores have been set on the product.');
    $this->drupalGet($form_url);
    $this->assertFieldByName('stores[target_id][value]', $store_labels[0] . ', ' . $store_labels[1]);
  }

  /**
   * Creates stores.
   *
   * @param string $num_stores
   *   The number of stores to create.
   */
  protected function createStores($num_stores) {
    for ($i = 0; $i < $num_stores; $i++) {
      $this->stores[] = $this->createEntity('commerce_store', [
        'type' => 'default',
        'name' => $this->randomMachineName(8),
        'mail' => \Drupal::currentUser()->getEmail(),
        'default_currency' => 'EUR',
      ]);
    }
  }

  /**
   * Creates a new entity
   *
   * @param string $entityType
   *   The entity type.
   * @param array $values
   *   The values used to create the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createEntity($entityType, $values) {
    $storage = \Drupal::entityManager()->getStorage($entityType);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id()
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

  /**
   * Asserts that the passed field values are correct.
   *
   * Ignores differences in ordering.
   *
   * @param array $field_values
   *   The field values.
   * @param array $expected_values
   *   The expected values.
   * @param $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertFieldValues(array $field_values, array $expected_values, $message = '') {
    $valid = TRUE;
    if (count($field_values) == count($expected_values)) {
      foreach ($expected_values as $value) {
        if (!in_array($value, $field_values)) {
          $valid = FALSE;
          break;
        }
      }
    }
    else {
      $valid = FALSE;
    }

    $this->assertTrue($valid, $message);
  }

}
