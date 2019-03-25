<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\OrderItemType;

/**
 * Tests the order item type UI.
 *
 * @group commerce
 */
class OrderItemTypeTest extends OrderBrowserTestBase {

  /**
   * Tests adding an order item type.
   */
  public function testAdd() {
    $this->drupalGet('admin/commerce/config/order-item-types/add');
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Foo order item type.');

    $order_item_type = OrderItemType::load($edit['id']);
    $this->assertNotEmpty($order_item_type);
    $this->assertEquals($edit['label'], $order_item_type->label());
    $this->assertEquals($edit['purchasableEntityType'], $order_item_type->getPurchasableEntityTypeId());
    $this->assertEquals($edit['orderType'], $order_item_type->getOrderTypeId());
  }

  /**
   * Tests editing an order item type.
   */
  public function testEdit() {
    $this->drupalGet('admin/commerce/config/order-item-types/default/edit');
    $edit = [
      'label' => 'Default!',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Default! order item type.');

    $order_item_type = OrderItemType::load('default');
    $this->assertEquals($edit['label'], $order_item_type->label());
  }

  /**
   * Tests duplicating an order item type.
   */
  public function testDuplicate() {
    $this->drupalGet('admin/commerce/config/order-item-types/default/duplicate');
    $this->assertSession()->fieldValueEquals('label', 'Default');
    $edit = [
      'label' => 'Default2',
      'id' => 'default2',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Default2 order item type.');

    // Confirm that the original order item type is unchanged.
    $order_item_type = OrderItemType::load('default');
    $this->assertNotEmpty($order_item_type);
    $this->assertEquals('Default', $order_item_type->label());

    // Confirm that the new order item type has the expected data.
    $order_item_type = OrderItemType::load('default2');
    $this->assertNotEmpty($order_item_type);
    $this->assertEquals('Default2', $order_item_type->label());
  }

  /**
   * Tests deleting an order item type.
   */
  public function testDelete() {
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type = $this->createEntity('commerce_order_item_type', [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => 'commerce_product_variation',
      'orderType' => 'default',
    ]);

    // Confirm that the delete page is not available when the type is locked.
    $order_item_type->lock();
    $order_item_type->save();
    $this->drupalGet($order_item_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals('403');

    // Unlock the type, confirm that deletion works.
    $order_item_type->unlock();
    $order_item_type->save();
    $this->drupalGet($order_item_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $order_item_type_exists = (bool) OrderItemType::load($order_item_type->id());
    $this->assertEmpty($order_item_type_exists);
  }

}
