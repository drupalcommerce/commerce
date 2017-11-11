<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_product\Entity\Product;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

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
    'commerce_product',
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
   * The test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->referenceField = FieldStorageConfig::loadByName('commerce_product', 'stores');
    $display = EntityFormDisplay::load('commerce_product.default.default');
    $display->setComponent('stores', [
      'type' => 'commerce_entity_select',
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
    // Set the first store.
    $this->stores[] = $this->store;
  }

  /**
   * Tests widget's hidden input type.
   */
  public function testWidget() {
    $form_url = 'product/' . $this->product->id() . '/edit';
    // Since the field is required, the widget should be a hidden element.
    $store_id = $this->stores[0]->id();
    $this->drupalGet($form_url);
    $field = $this->getSession()->getPage()->find('xpath', '//input[@type="hidden" and @name="stores[target_id][value]" and @value="' . $store_id . '"]');
    $this->assertNotEmpty($field);

    // Create another store. The widget should now be a set of checkboxes.
    $this->createStores(1);
    $store_ids = EntityHelper::extractIds($this->stores);
    $this->drupalGet($form_url);
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@type="checkbox" and starts-with(@name,"stores")]'));
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-1');
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-2');
    // Check store 1.
    $edit['stores[target_id][value][' . $store_ids[0] . ']'] = $store_ids[0];
    $edit['stores[target_id][value][' . $store_ids[1] . ']'] = FALSE;
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    \Drupal::entityTypeManager()->getStorage('commerce_product')->resetCache();
    $this->product = Product::load($this->product->id());
    $this->assertFieldValues($this->product->getStoreIds(), [$store_ids[0]], 'The correct store has been set on the product.');
    $this->drupalGet($form_url);
    $this->assertSession()->checkboxChecked('edit-stores-target-id-value-' . $store_ids[0]);
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-' . $store_ids[1]);

    // Reduce the cardinality to 1. Checkboxes should now be radios.
    $this->referenceField->setCardinality(1)->save();
    $this->drupalGet($form_url);
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@type="radio" and @name="stores[target_id][value]"]'));
    $this->assertSession()->checkboxChecked('edit-stores-target-id-value-' . $store_ids[0]);
    $this->assertSession()->checkboxNotChecked('edit-stores-target-id-value-' . $store_ids[1]);

    // Create the final store. The widget should now be an autocomplete field.
    $this->createStores(1);
    $store_labels = array_map(function ($store) {
      return $store->label() . ' (' . $store->id() . ')';
    }, $this->stores);
    $this->referenceField->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED)->save();
    $this->drupalGet($form_url);
    $this->assertNotNull($this->getSession()->getPage()->find('xpath', '//input[@id="edit-stores-target-id-value" and starts-with(@class, "form-autocomplete")]'));
    $this->assertSession()->fieldValueEquals('stores[target_id][value]', $store_labels[0]);
    // Reference both stores 1 and 2.
    $edit = [];
    $edit['stores[target_id][value]'] = $store_labels[0] . ', ' . $store_labels[1];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    \Drupal::entityTypeManager()->getStorage('commerce_product')->resetCache();
    $this->product = Product::load($this->product->id());
    $this->assertFieldValues($this->product->getStoreIds(), [$store_ids[0], $store_ids[1]], 'The correct stores have been set on the product.');
    $this->drupalGet($form_url);
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
