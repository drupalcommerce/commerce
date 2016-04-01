<?php

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Create, edit, delete, and change product attributes.
 *
 * @group commerce
 */
class ProductAttributeTest extends ProductTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer product attributes',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creation of a product attribute.
   */
  public function testProductAttributeCreation() {
    $this->drupalGet('admin/commerce/product-attributes');
    $this->clickLink('Add product attribute');
    $this->drupalPostForm(NULL, [
      'label' => 'Test Creation',
      'id' => 'test_creation',
    ], 'Save');
    $this->assertText("Created the Test Creation product attribute.");
    $this->assertUrl("admin/commerce/product-attributes/manage/test_creation/overview");

    $attribute = ProductAttribute::load('test_creation');
    $this->assertEqual($attribute->label(), 'Test Creation');
  }

  /**
   * Tests editing a product attribute.
   */
  public function testProductAttributeEditing() {
    ProductAttribute::create([
      'label' => 'Test Editing',
      'id' => 'test_editing',
    ])->save();
    $this->drupalGet('admin/commerce/product-attributes/manage/test_editing');
    $this->drupalPostForm(NULL, [
      'label' => 'Test Edit',
    ], 'Save values');
    $this->assertText('Updated the Test Edit product attribute.');
    $this->assertUrl('admin/commerce/product-attributes');

    $attribute = ProductAttribute::load('test_editing');
    $this->assertEqual($attribute->label(), 'Test Edit');
  }

  /**
   * Tests deletion of a product attribute.
   */
  public function testProductAttributeDeletion() {
    /** @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager */
    $attribute_field_manager = \Drupal::service('commerce_product.attribute_field_manager');
    $attribute = ProductAttribute::create([
      'label' => 'Test Deleting',
      'id' => 'test_deleting',
    ]);
    $attribute->save();
    $this->assertTrue($attribute_field_manager->canDeleteField($attribute));
    $attribute->delete();
    $this->assertNull(ProductAttribute::load('test_deleting'));

    $variation = ProductVariationType::create([
      'label' => 'Testing attributes',
      'id' => 'testing_attributes',
      'generateTitle' => 1,
      'lineItemType' => 'default',
    ]);
    $attribute = ProductAttribute::create([
      'label' => 'Test Deleting 2',
      'id' => 'test_deleting2',
    ]);
    $attribute->save();
    $attribute_field_manager->createField($attribute, $variation->id());
    $this->assertFalse($attribute_field_manager->canDeleteField($attribute));

  }

  /**
   * Tests creating a product attribute, and modifying list values.
   */
  public function testProductAttributeOverview() {
    $this->drupalGet('admin/commerce/product-attributes');
    $this->clickLink('Add product attribute');
    $this->drupalPostForm(NULL, [
      'label' => 'Color',
      'id' => 'color',
    ], 'Save');
    $this->assertText('Created the Color product attribute.');
    $this->assertUrl('admin/commerce/product-attributes/manage/color/overview');

    // Add three extra options.
    $this->drupalPostAjaxForm(NULL, [], ['op' => 'Add']);
    $this->drupalPostAjaxForm(NULL, [], ['op' => 'Add']);
    $this->drupalPostAjaxForm(NULL, [], ['op' => 'Add']);

    $this->drupalPostForm(NULL, [
      'values[0][entity][name][0][value]' => 'Cyan',
      'values[1][entity][name][0][value]' => 'Yellow',
      'values[2][entity][name][0][value]' => 'Magenta',
      'values[3][entity][name][0][value]' => 'Black',
    ], 'Save values');
    $this->assertText('Saved the Color attribute values.');

    $attribute = ProductAttribute::load('color');
    $attribute_values = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
    foreach ($attribute->getValues() as $value) {
      $attribute_values[] = $value->label();
    }

    $this->assertTrue($attribute_values[0] == 'Cyan');
    $this->assertTrue($attribute_values[1] == 'Yellow');
    $this->assertTrue($attribute_values[2] == 'Magenta');
    $this->assertTrue($attribute_values[3] == 'Black');

    $this->drupalPostAjaxForm(NULL, [], ['remove_value1' => 'Remove']);
    $this->drupalPostAjaxForm(NULL, [], ['remove_value3' => 'Remove']);
    $this->drupalPostAjaxForm(NULL, [], ['op' => 'Add']);
    $this->drupalPostForm(NULL, [
      'values[3][entity][name][0][value]' => 'Cornflower Blue',
    ], 'Save values');
    $this->drupalPostForm(NULL, [], 'Save values');

    $attribute = ProductAttribute::load('color');
    $attribute_values = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
    foreach ($attribute->getValues() as $value) {
      $attribute_values[] = $value->label();
    }

    $this->assertTrue($attribute_values[0] == 'Cyan');
    $this->assertTrue($attribute_values[1] == 'Magenta');
    $this->assertTrue($attribute_values[2] == 'Cornflower Blue');
  }

}
