<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests the product type UI.
 *
 * @group commerce
 */
class ProductTypeTest extends ProductBrowserTestBase {

  /**
   * Tests whether the default product type was created.
   */
  public function testDefault() {
    $product_type = ProductType::load('default');
    $this->assertNotEmpty($product_type);

    $this->drupalGet('admin/commerce/config/product-types');
    $rows = $this->getSession()->getPage()->findAll('css', 'table tbody tr');
    $this->assertCount(1, $rows);
  }

  /**
   * Tests adding a product type.
   */
  public function testAdd() {
    $user = $this->drupalCreateUser(['administer commerce_product_type']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/commerce/config/product-types/add');

    $variation_type_field = $this->getSession()->getPage()->findField('variationType');
    $this->assertFalse($variation_type_field->hasAttribute('disabled'));
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
      'description' => 'My even more random product type',
      'variationType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('The product type Foo has been successfully saved.');

    $product_type = ProductType::load($edit['id']);
    $this->assertNotEmpty($product_type);
    $this->assertEquals($edit['label'], $product_type->label());
    $this->assertEquals($edit['description'], $product_type->getDescription());
    $this->assertEquals($edit['variationType'], $product_type->getVariationTypeId());
    $this->assertTrue($product_type->allowsMultipleVariations());
    $this->assertTrue($product_type->shouldInjectVariationFields());
    $form_display = commerce_get_entity_display('commerce_product', $edit['id'], 'form');
    $this->assertEmpty($form_display->getComponent('variations'));

    // Automatic variation type creation option, single variation mode.
    $this->drupalGet('admin/commerce/config/product-types/add');
    $edit = [
      'id' => 'foo2',
      'label' => 'Foo2',
      'description' => 'My even more random product type',
      'variationType' => '',
      'multipleVariations' => FALSE,
      'injectVariationFields' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $product_type = ProductType::load($edit['id']);
    $this->assertNotEmpty($product_type);
    $this->assertEquals($edit['label'], $product_type->label());
    $this->assertEquals($edit['description'], $product_type->getDescription());
    $this->assertEquals($edit['id'], $product_type->getVariationTypeId());
    $variation_type = ProductVariationType::load($edit['id']);
    $this->assertNotEmpty($variation_type);
    $this->assertEquals($variation_type->label(), $edit['label']);
    $this->assertEquals($variation_type->getOrderItemTypeId(), 'default');
    $this->assertFalse($product_type->allowsMultipleVariations());
    $this->assertFalse($product_type->shouldInjectVariationFields());
    $form_display = commerce_get_entity_display('commerce_product', $edit['id'], 'form');
    $component = $form_display->getComponent('variations');
    $this->assertNotEmpty($component);
    $this->assertEquals('commerce_product_single_variation', $component['type']);

    // Confirm that a conflicting product variation type ID is detected.
    $product_type_id = $product_type->id();
    $product_type->delete();
    $this->drupalGet('admin/commerce/config/product-types/add');
    $edit = [
      'id' => $product_type_id,
      'label' => $this->randomMachineName(),
      'description' => 'My even more random product type',
      'variationType' => '',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('A product variation type with the machine name @name already exists. Select an existing product variation type or change the machine name for this product type.', ['@name' => $product_type_id]));

    // Confirm that the form can't be submitted with no order item types.
    $default_order_item_type = OrderItemType::load('default');
    $this->assertNotEmpty($default_order_item_type);
    $default_order_item_type->delete();

    $this->drupalGet('admin/commerce/config/product-types/add');
    $edit = [
      'id' => 'foo3',
      'label' => 'Foo3',
      'description' => 'Another random product type',
      'variationType' => '',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('A new product variation type cannot be created, because no order item types were found. Select an existing product variation type or retry after creating a new order item type.'));

    // Confirm that a non-default order item type can be selected.
    $default_order_item_type->delete();
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
      'purchasableEntityType' => 'commerce_product_variation',
    ])->save();

    $this->drupalGet('admin/commerce/config/product-types/add');
    $edit = [
      'id' => 'foo4',
      'label' => 'Foo4',
      'description' => 'My even more random product type',
      'variationType' => '',
    ];
    $this->submitForm($edit, t('Save'));
    $product_type = ProductType::load($edit['id']);
    $this->assertNotEmpty($product_type);
    $this->assertEquals($edit['label'], $product_type->label());
    $this->assertEquals($edit['description'], $product_type->getDescription());
    $this->assertEquals($edit['id'], $product_type->getVariationTypeId());
    $variation_type = ProductVariationType::load($edit['id']);
    $this->assertNotEmpty($variation_type);
    $this->assertEquals($edit['label'], $variation_type->label());
    $this->assertEquals('test', $variation_type->getOrderItemTypeId());
  }

  /**
   * Tests editing a product type.
   */
  public function testEdit() {
    $this->drupalGet('admin/commerce/config/product-types/default/edit');

    $variation_type_field = $this->getSession()->getPage()->findField('variationType');
    $this->assertFalse($variation_type_field->hasAttribute('disabled'));
    $edit = [
      'label' => 'Default!',
      'description' => 'New description.',
      'multipleVariations' => FALSE,
      'injectVariationFields' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('The product type Default! has been successfully saved.');

    $product_type = ProductType::load('default');
    $this->assertEquals($edit['label'], $product_type->label());
    $this->assertEquals($edit['description'], $product_type->getDescription());
    $this->assertFalse($product_type->allowsMultipleVariations());
    $this->assertFalse($product_type->shouldInjectVariationFields());
    // Confirm that the product display was updated.
    $form_display = commerce_get_entity_display('commerce_product', 'default', 'form');
    $component = $form_display->getComponent('variations');
    $this->assertNotEmpty($component);
    $this->assertEquals('commerce_product_single_variation', $component['type']);

    // Re-enable multiple variations.
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $edit = [
      'multipleVariations' => TRUE,
    ];
    $this->submitForm($edit, t('Save'));
    \Drupal::entityTypeManager()->getStorage('commerce_product_type')->resetCache();
    $product_type = ProductType::load('default');
    $this->assertTrue($product_type->allowsMultipleVariations());
    // Confirm that the product display was updated.
    $form_display = commerce_get_entity_display('commerce_product', 'default', 'form');
    $this->assertEmpty($form_display->getComponent('variations'));

    // Cannot change the variation type once a product has been created.
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Test product',
    ]);
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $variation_type_field = $this->getSession()->getPage()->findField('variationType');
    $this->assertTrue($variation_type_field->hasAttribute('disabled'));
  }

  /**
   * Tests duplicating a product type.
   */
  public function testDuplicate() {
    $this->drupalGet('admin/commerce/config/product-types/default/duplicate');
    $this->assertSession()->fieldValueEquals('label', 'Default');
    $edit = [
      'label' => 'Default2',
      'id' => 'default2',
      'multipleVariations' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('The product type Default2 has been successfully saved.');

    // Confirm that the original product type is unchanged.
    $product_type = ProductType::load('default');
    $this->assertNotEmpty($product_type);
    $this->assertEquals('Default', $product_type->label());

    // Confirm that the new product type has the expected data.
    $product_type = ProductType::load('default2');
    $this->assertNotEmpty($product_type);
    $this->assertEquals('Default2', $product_type->label());
    $this->assertFalse($product_type->allowsMultipleVariations());

    // Confirm that the form display is correct.
    $form_display = commerce_get_entity_display('commerce_product', 'default2', 'form');
    $component = $form_display->getComponent('variations');
    $this->assertNotEmpty($component);
    $this->assertEquals('commerce_product_single_variation', $component['type']);
  }

  /**
   * Tests deleting a product type.
   */
  public function testDelete() {
    $variation_type = $this->createEntity('commerce_product_variation_type', [
      'id' => 'foo',
      'label' => 'foo',
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
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

    // Confirm that the type can't be deleted while there's a product.
    $this->drupalGet($product_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('@type is used by 1 product on your site. You cannot remove this product type until you have removed all of the @type products.', ['@type' => $product_type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Confirm that the delete page is not available when the type is locked.
    $product_type->lock();
    $product_type->save();
    $this->drupalGet($product_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals('403');

    // Delete the product, unlock the type, confirm that deletion works.
    $product->delete();
    $product_type->unlock();
    $product_type->save();
    $this->drupalGet($product_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the product type @type?', ['@type' => $product_type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');
    $exists = (bool) ProductType::load($product_type->id());
    $this->assertEmpty($exists);
  }

}
