<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductType;

/**
 * Ensure the product type works correctly.
 *
 * @group commerce
 */
class ProductTypeTest extends ProductBrowserTestBase {

  /**
   * Tests whether the default product type was created.
   */
  public function testDefaultProductType() {
    $product_type = ProductType::load('default');
    $this->assertNotEmpty(!empty($product_type), 'The default product type is available.');

    $this->drupalGet('admin/commerce/config/product-types');
    $rows = $this->getSession()->getPage()->find('css', 'table tbody tr');
    $this->assertEquals(count($rows), 1, '1 product type is correctly listed.');
  }

  /**
   * Tests creating a product type programmatically and via a form.
   */
  public function testProductTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'description' => 'My random product type',
      'variationType' => 'default',
    ];
    $this->createEntity('commerce_product_type', $values);
    $product_type = ProductType::load($values['id']);
    $this->assertEquals($product_type->label(), $values['label'], 'The new product type has the correct label.');
    $this->assertEquals($product_type->getDescription(), $values['description'], 'The new product type has the correct label.');
    $this->assertEquals($product_type->getVariationTypeId(), $values['variationType'], 'The new product type has the correct associated variation type.');

    $this->drupalGet('product/add/' . $product_type->id());
    $this->assertSession()->statusCodeEquals(200);

    $user = $this->drupalCreateUser(['administer commerce_product_type']);
    $this->drupalLogin($user);

    $this->drupalGet('admin/commerce/config/product-types/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
      'description' => 'My even more random product type',
      'variationType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $product_type = ProductType::load($edit['id']);
    $this->assertNotEmpty(!empty($product_type), 'The new product type has been created.');
    $this->assertEquals($product_type->label(), $edit['label'], 'The new product type has the correct label.');
    $this->assertEquals($product_type->getDescription(), $edit['description'], 'The new product type has the correct label.');
    $this->assertEquals($product_type->getVariationTypeId(), $edit['variationType'], 'The new product type has the correct associated variation type.');
  }

  /**
   * Tests editing a product type using the UI.
   */
  public function testProductTypeEditing() {
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $edit = [
      'label' => 'Default2',
      'description' => 'New description.',
    ];
    $this->submitForm($edit, t('Save'));
    $product_type = ProductType::load('default');
    $this->assertEquals($product_type->label(), $edit['label'], 'The label of the product type has been changed.');
    $this->assertEquals($product_type->getDescription(), $edit['description'], 'The new product type has the correct label.');
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
    $this->assertSession()->pageTextContains(
      t('@type is used by 1 product on your site. You cannot remove this product type until you have removed all of the @type products.', ['@type' => $product_type->label()]),
      'The product type will not be deleted until all products of that type are deleted.'
    );
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    $product->delete();
    $this->drupalGet('admin/commerce/config/product-types/' . $product_type->id() . '/delete');
    $this->assertSession()->pageTextContains(
      t('Are you sure you want to delete the product type @type?', ['@type' => $product_type->label()]),
      'The product type is available for deletion'
    );
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');
    $exists = (bool) ProductType::load($product_type->id());
    $this->assertEmpty($exists, 'The new product type has been deleted from the database.');
  }

}
