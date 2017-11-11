<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Create, edit, delete, and change product attributes.
 *
 * @group commerce
 */
class ProductAttributeTest extends ProductBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product_attribute',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creation of a product attribute.
   */
  public function testProductAttributeCreation() {
    $this->drupalGet('admin/commerce/product-attributes');
    $this->getSession()->getPage()->clickLink('Add product attribute');
    $this->submitForm([
      'label' => 'Size',
      'elementType' => 'commerce_product_rendered_attribute',
      // Setting the 'id' can fail if focus switches to another field.
      // This is a bug in the machine name JS that can be reproduced manually.
      'id' => 'size',
    ], 'Save');
    $this->assertSession()->pageTextContains('Created the Size product attribute.');
    $this->assertSession()->addressMatches('/\/admin\/commerce\/product-attributes\/manage\/size$/');

    $attribute = ProductAttribute::load('size');
    $this->assertEquals($attribute->label(), 'Size');
    $this->assertEquals($attribute->getElementType(), 'commerce_product_rendered_attribute');
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
    $this->submitForm([
      'label' => 'Colour',
      'elementType' => 'radios',
      'values[0][entity][name][0][value]' => 'Red',
    ], 'Save');
    $this->assertSession()->pageTextContains('Updated the Colour product attribute.');
    $this->assertSession()->addressMatches('/\/admin\/commerce\/product-attributes$/');

    $attribute = ProductAttribute::load('color');
    $this->assertEquals($attribute->label(), 'Colour');
    $this->assertEquals($attribute->getElementType(), 'radios');
  }

  /**
   * Tests deletion of a product attribute.
   */
  public function testProductAttributeDeletion() {
    $this->createEntity('commerce_product_attribute', [
      'id' => 'size',
      'label' => 'Size',
    ]);
    $this->drupalGet('admin/commerce/product-attributes/manage/size/delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete the product attribute Size?');
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');

    $this->assertNull(ProductAttribute::load('size'));
  }

  /**
   * Tests assigning an attribute to a product variation type.
   */
  public function testProductVariationTypes() {
    $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);

    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $edit = [
      'variation_types[default]' => 'default',
      'values[0][entity][name][0][value]' => 'Red',
    ];
    $this->submitForm($edit, t('Save'));
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextContains('attribute_color', 'The color attribute field has been created');

    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $edit = [
      'variation_types[default]' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit/fields');
    $this->assertSession()->pageTextNotContains('attribute_color', 'The color attribute field has been deleted');
  }

  /**
   * Tests a manually created attribute field.
   *
   * The attribute and variation type forms should handle the existence
   * of manually-created attribute fields without crashing.
   */
  public function testManualAttributeField() {
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
    $attribute = $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_my_attribute',
      'entity_type' => 'commerce_product_variation',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'commerce_product_attribute_value',
      ],
      'translatable' => FALSE,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'default',
      'label' => 'Custom Color',
      'required' => TRUE,
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => ['color'],
        ],
      ],
      'translatable' => FALSE,
    ]);
    $field->save();

    $this->drupalGet($attribute->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $variation_type_checkbox = $page->findField('Default');
    $this->assertTrue($variation_type_checkbox->isChecked());
    $this->assertTrue($variation_type_checkbox->hasAttribute('disabled'));

    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $attribute_checkbox = $page->findField('Color');
    $this->assertTrue($attribute_checkbox->isChecked());
    $this->assertTrue($attribute_checkbox->hasAttribute('disabled'));
  }

}
