<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\LineItemTypeTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Entity\LineItemType;

/**
 * Tests the commerce_line_item_type entity type.
 *
 * @group commerce
 */
class LineItemTypeTest extends OrderTestBase {

  /**
   * Tests creating a line item type programmatically and through the add form.
   */
  public function testLineItemTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->createEntity('commerce_line_item_type', $values);
    $line_item_type = LineItemType::load($values['id']);
    $this->assertEqual($line_item_type->label(), $values['label'], 'The new line item type has the correct label.');
    $this->assertEqual($line_item_type->getPurchasableEntityType(), $values['purchasableEntityType'], 'The new line item type has the correct purchasable entity type.');
    $this->assertEqual($line_item_type->getOrderType(), $values['orderType'], 'The new line item type has the correct order type.');

    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->drupalPostForm('admin/commerce/config/line-item-types/add', $edit, t('Save'));
    $line_item_type = LineItemType::load($edit['id']);
    $this->assertEqual($line_item_type->label(), $edit['label'], 'The new line item type has the correct label.');
    $this->assertEqual($line_item_type->getPurchasableEntityType(), $edit['purchasableEntityType'], 'The new line item type has the correct purchasable entity type.');
    $this->assertEqual($line_item_type->getOrderType(), $edit['orderType'], 'The new line item type has the correct order type.');
  }

  /**
   * Tests updating a line item type through the edit form.
   */
  public function testLineItemTypeEditing() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->createEntity('commerce_line_item_type', $values);

    $edit = [
      'label' => $this->randomMachineName(16),
    ];
    $this->drupalPostForm('admin/commerce/config/line-item-types/' . $values['id'] . '/edit', $edit, t('Save'));
    $line_item_type = LineItemType::load($values['id']);
    $this->assertEqual($line_item_type->label(), $edit['label'], 'The label of the line item type has been changed.');
  }

  /**
   * Tests deleting a line item type programmatically and through the form.
   */
  public function testLineItemTypeDeletion() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $line_item_type = $this->createEntity('commerce_line_item_type', $values);

    $this->drupalGet('admin/commerce/config/line-item-types/' . $line_item_type->id() . '/delete');
    $this->assertResponse(200, 'line item type delete form can be accessed at admin/commerce/config/line-item-types/'. $line_item_type->id() . '/delete.');
    $this->assertText(t('This action cannot be undone.'), 'The line item type deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $line_item_type_exists = (bool) LineItemType::load($line_item_type->id());
    $this->assertFalse($line_item_type_exists, 'The line item type has been deleted form the database.');
  }

}
