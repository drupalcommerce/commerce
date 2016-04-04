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
      'id' => 'size',
      'label' => 'Size',
    ], 'Save');
    $this->assertText("Created the Size product attribute.");
    $this->assertUrl("admin/commerce/product-attributes/manage/size/overview");

    $attribute = ProductAttribute::load('size');
    $this->assertEqual($attribute->label(), 'Size');
  }

  /**
   * Tests editing a product attribute.
   */
  public function testProductAttributeEditing() {
    $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $this->drupalPostForm(NULL, [
      'label' => 'Colour',
    ], 'Save');
    $this->assertText('Updated the Colour product attribute.');
    $this->assertUrl('admin/commerce/product-attributes');

    $attribute = ProductAttribute::load('color');
    $this->assertEqual($attribute->label(), 'Colour');
  }

  /**
   * Tests deletion of a product attribute.
   */
  public function testProductAttributeDeletion() {
    $attribute = $this->createEntity('commerce_product_attribute', [
      'id' => 'size',
      'label' => 'Size',
    ]);
    $this->drupalGet('admin/commerce/product-attributes/manage/size/delete');
    $this->assertText('Are you sure you want to delete the product attribute Size?', "Delete confirmation text is shown");
    $this->assertText('This action cannot be undone.', 'The attribute delete confirmation form is available');
    $this->drupalPostForm(NULL, NULL, 'Delete');

    $this->assertNull(ProductAttribute::load('size'));
  }

  /**
   * Tests creating a product attribute, and modifying list values.
   */
  public function testProductAttributeOverview() {
    $this->drupalGet('admin/commerce/product-attributes');
    $this->clickLink('Add product attribute');
    $this->drupalPostForm(NULL, [
      'id' => 'color',
      'label' => 'Color',
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
