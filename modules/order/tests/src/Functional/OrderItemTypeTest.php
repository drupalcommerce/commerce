<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\OrderItemType;

/**
 * Tests the commerce_order_item_type entity type.
 *
 * @group commerce
 */
class OrderItemTypeTest extends OrderBrowserTestBase {

  /**
   * Tests creating an order item type programmatically and through the add form.
   */
  public function testOrderItemTypeCreation() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->createEntity('commerce_order_item_type', $values);
    $order_item_type = OrderItemType::load($values['id']);
    $this->assertEquals($order_item_type->label(), $values['label'], 'The new order item type has the correct label.');
    $this->assertEquals($order_item_type->getPurchasableEntityTypeId(), $values['purchasableEntityType'], 'The new order item type has the correct purchasable entity type.');
    $this->assertEquals($order_item_type->getOrderTypeId(), $values['orderType'], 'The new order item type has the correct order type.');

    $this->drupalGet('admin/commerce/config/order-item-types/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $order_item_type = OrderItemType::load($edit['id']);
    $this->assertEquals($order_item_type->label(), $edit['label'], 'The new order item type has the correct label.');
    $this->assertEquals($order_item_type->getPurchasableEntityTypeId(), $edit['purchasableEntityType'], 'The new order item type has the correct purchasable entity type.');
    $this->assertEquals($order_item_type->getOrderTypeId(), $edit['orderType'], 'The new order item type has the correct order type.');
  }

  /**
   * Tests updating an order item type through the edit form.
   */
  public function testOrderItemTypeEditing() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $type */
    $order_item_type = $this->createEntity('commerce_order_item_type', $values);

    $this->drupalGet($order_item_type->toUrl('edit-form'));
    $edit = [
      'label' => $this->randomMachineName(16),
    ];
    $this->submitForm($edit, t('Save'));
    $order_item_type = OrderItemType::load($values['id']);
    $this->assertEquals($order_item_type->label(), $edit['label'], 'The label of the order item type has been changed.');
  }

  /**
   * Tests deleting an order item type programmatically and through the form.
   */
  public function testOrderItemTypeDeletion() {
    $values = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $order_item_type = $this->createEntity('commerce_order_item_type', $values);

    $this->drupalGet($order_item_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $order_item_type_exists = (bool) OrderItemType::load($order_item_type->id());
    $this->assertEmpty($order_item_type_exists, 'The order item type has been deleted form the database.');
  }

}
