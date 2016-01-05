<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductVariationTypeTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Component\Utility\Unicode;

/**
 * Ensure the product variation type works correctly.
 *
 * @group commerce
 */
class ProductVariationTypeTest extends ProductTestBase {

  /**
   * Tests whether the default product variation type was created.
   */
  public function testDefaultProductVariationType() {
    $variation_type = ProductVariationType::load('default');
    $this->assertTrue($variation_type, 'The default product variation type is available.');

    $this->drupalGet('admin/commerce/config/product-variation-types');
    $rows = $this->cssSelect('table tbody tr');
    $this->assertEqual(count($rows), 1, '1 product variation type is correctly listed.');
  }

  /**
   * Tests creating a product variation type programmatically and via a form.
   */
  function testProductVariationTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'lineItemType' => 'product_variation',
    ];
    $variation_type = $this->createEntity('commerce_product_variation_type', $values);
    $variation_type = ProductVariationType::load($values['id']);
    $this->assertEqual($variation_type->label(), $values['label'], 'The new product variation type has the correct label.');
    $this->assertEqual($variation_type->getLineItemType(), $values['lineItemType'], 'The new product variation type has the correct line item type.');

    $user = $this->drupalCreateUser(['administer product types']);
    $this->drupalLogin($user);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'lineItemType' => 'product_variation',
    ];
    $this->drupalPostForm('admin/commerce/config/product-variation-types/add', $edit, t('Save'));
    $variation_type = ProductVariationType::load($edit['id']);
    $this->assertTrue($variation_type, 'The new product variation type has been created.');
    $this->assertEqual($variation_type->label(), $edit['label'], 'The new product variation type has the correct label.');
    $this->assertEqual($variation_type->getLineItemType(), $edit['lineItemType'], 'The new product variation type has the correct line item type.');
  }

  /**
   * Tests editing a product variation type using the UI.
   */
  function testProductVariationTypeEditing() {
    $edit = [
      'label' => 'Default2',
    ];
    $this->drupalPostForm('admin/commerce/config/product-variation-types/default/edit', $edit, t('Save'));
    $variation_type = ProductVariationType::load('default');
    $this->assertEqual($variation_type->label(), 'Default2', 'The label of the product variation type has been changed.');
  }

  /**
   * Tests deleting a product variation type via a form.
   */
  public function testProductVariationTypeDeletion() {
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo'
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => $variation_type->id(),
      'sku' => $this->randomMachineName(),
      'title' => $this->randomMachineName(),
    ]);

    // @todo Make sure $variation_type->delete() also does nothing if there's
    // a variation of that type. Right now the check is done on the form level.
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variation_type->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 product variation on your site. You can not remove this product variation type until you have removed all of the %type product variations.', ['%type' => $variation_type->label()]),
      'The product variation type will not be deleted until all variations of that type are deleted.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The product variation type deletion confirmation form is not available');

    $variation->delete();
    $this->drupalGet('admin/commerce/config/product-variation-types/' . $variation_type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the product variation type %type?', ['%type' => $variation_type->label()]),
      'The product variation type is available for deletion'
    );
    $this->assertText(t('This action cannot be undone.'), 'The product variation type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $exists = (bool) ProductVariationType::load($variation_type->id());
    $this->assertFalse($exists, 'The new product variation type has been deleted from the database.');
  }

  /**
   * Tests the attribute field settings.
   */
  function testAttributeFieldSettingsAdmin() {
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo'
    ]);
    // The edit form fails validation if target_bundles is empty.
    $this->createEntityReferenceField('commerce_product_variation', 'foo', 'field_attribute', 'Attribute', 'taxonomy_term', 'default', ['target_bundles' => ['taxonomy_term']]);

    // Create a vocabulary otherwise we can't submit the field settings form.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => Unicode::strtolower($this->randomMachineName()),
    ));
    $vocabulary->save();

    $edit = [
      'attribute_field' => 1,
      'attribute_widget' => 'select',
      'attribute_widget_title' => $this->randomMachineName(),
      'settings[handler_settings][target_bundles][' . $vocabulary->getOriginalId() . ']' => 1,
    ];
    $this->drupalPostForm('admin/commerce/config/product-variation-types/foo/edit/fields/commerce_product_variation.foo.field_attribute', $edit, t('Save settings'));

    $this->drupalGet('admin/commerce/config/product-variation-types/foo/edit/fields/commerce_product_variation.foo.field_attribute');
    $this->assertFieldChecked('edit-attribute-field', 'Attribute field setting is set.');
    $this->assertFieldChecked('edit-attribute-widget-select', 'Attribute widget setting field is set.');
    $this->assertFieldChecked('edit-settings-handler-settings-target-bundles-' . $vocabulary->getOriginalId(), 'Vocabulary is selected.');
    $this->assertField('attribute_widget_title', $edit['attribute_widget_title'], 'Attribute widget title setting is set.');
  }

}
