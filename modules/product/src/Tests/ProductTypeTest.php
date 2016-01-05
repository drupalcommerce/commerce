<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Tests\ProductTypeTest.
 */

namespace Drupal\commerce_product\Tests;

use Drupal\commerce_product\Entity\ProductType;

/**
 * Ensure the product type works correctly.
 *
 * @group commerce
 */
class ProductTypeTest extends ProductTestBase {

  /**
   * Tests whether the default product type was created.
   */
  public function testDefaultProductType() {
    $product_type = ProductType::load('default');
    $this->assertTrue($product_type, 'The default product type is available.');

    $this->drupalGet('admin/commerce/config/product-types');
    $rows = $this->cssSelect('table tbody tr');
    $this->assertEqual(count($rows), 1, '1 product type is correctly listed.');
  }

  /**
   * Tests creating a product type programmatically and via a form.
   */
  function testProductTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'description' => 'My random product type',
      'variationType' => 'default',
    ];
    $product_type = $this->createEntity('commerce_product_type', $values);
    $product_type = ProductType::load($values['id']);
    $this->assertEqual($product_type->label(), $values['label'], 'The new product type has the correct label.');
    $this->assertEqual($product_type->getDescription(), $values['description'], 'The new product type has the correct label.');
    $this->assertEqual($product_type->getVariationType(), $values['variationType'], 'The new product type has the correct associated variation type.');

    $this->drupalGet('product/add/' . $product_type->id());
    $this->assertResponse(200, 'The new product type can be accessed at product/add.');

    $user = $this->drupalCreateUser(['administer product types']);
    $this->drupalLogin($user);
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'description' => 'My even more random product type',
      'variationType' => 'default',
    ];
    $this->drupalPostForm('admin/commerce/config/product-types/add', $edit, t('Save'));
    $product_type = ProductType::load($edit['id']);
    $this->assertTrue($product_type, 'The new product type has been created.');
    $this->assertEqual($product_type->label(), $edit['label'], 'The new product type has the correct label.');
    $this->assertEqual($product_type->getDescription(), $edit['description'], 'The new product type has the correct label.');
    $this->assertEqual($product_type->getVariationType(), $edit['variationType'], 'The new product type has the correct associated variation type.');
  }

  /**
   * Tests editing a product type using the UI.
   */
  function testProductTypeEditing() {
    $edit = [
      'label' => 'Default2',
      'description' => 'New description.',
    ];
    $this->drupalPostForm('admin/commerce/config/product-types/default/edit', $edit, t('Save'));
    $product_type = ProductType::load('default');
    $this->assertEqual($product_type->label(), $edit['label'], 'The label of the product type has been changed.');
    $this->assertEqual($product_type->getDescription(), $edit['description'], 'The new product type has the correct label.');
  }

  /**
   * Tests deleting a product type via a form.
   */
  public function testProductTypeDeletion() {
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo',
    ]);
    $product_type = $this->createEntity('commerce_product_type', [
      'id' => 'foo',
      'label' => 'foo',
      'variationType' => $variation_type->id(),
    ]);
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);

    $product = $this->createEntity('commerce_product', [
      'type' => $product_type->id(),
      'title' => $this->randomMachineName(),
    ]);

    // @todo Make sure $product_type->delete() also does nothing if there's
    // a product of that type. Right now the check is done on the form level.
    $this->drupalGet('admin/commerce/config/product-types/' . $product_type->id() . '/delete');
    $this->assertRaw(
      t('%type is used by 1 product on your site. You can not remove this product type until you have removed all of the %type products.', ['%type' => $product_type->label()]),
      'The product type will not be deleted until all products of that type are deleted.'
    );
    $this->assertNoText(t('This action cannot be undone.'), 'The product type deletion confirmation form is not available');

    $product->delete();
    $this->drupalGet('admin/commerce/config/product-types/' . $product_type->id() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the product type %type?', ['%type' => $product_type->label()]),
      'The product type is available for deletion'
    );
    $this->assertText(t('This action cannot be undone.'), 'The product type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $exists = (bool) ProductType::load($product_type->id());
    $this->assertFalse($exists, 'The new product type has been deleted from the database.');
  }

}
