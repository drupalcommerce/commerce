<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\OrderType;

/**
 * Tests the order type UI.
 *
 * @group commerce
 */
class OrderTypeTest extends OrderBrowserTestBase {

  /**
   * Tests whether the default order type was created.
   */
  public function testDefault() {
    $order_type = OrderType::load('default');
    $this->assertNotEmpty($order_type);

    $this->drupalGet('admin/commerce/config/order-types');
    $rows = $this->getSession()->getPage()->findAll('css', 'table tbody tr');
    $this->assertCount(1, $rows);
  }

  /**
   * Tests adding an order type.
   */
  public function testAdd() {
    // Remove the default order type to be able to test creating the
    // order_items field anew.
    OrderType::load('default')->delete();

    $this->drupalGet('admin/commerce/config/order-types/add');
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
      'refresh_mode' => 'always',
      'refresh_frequency' => 60,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Foo order type.');

    $order_type = OrderType::load('foo');
    $this->assertNotEmpty($order_type);
    $this->assertEmpty($order_type->getNumberPatternId());
    $this->assertEquals($edit['refresh_mode'], $order_type->getRefreshMode());
    $this->assertEquals($edit['refresh_frequency'], $order_type->getRefreshFrequency());
  }

  /**
   * Tests editing an order type.
   */
  public function testEdit() {
    $this->drupalGet('admin/commerce/config/order-types/default/edit');
    $edit = [
      'label' => 'Default!',
      'generate_number' => FALSE,
      'refresh_mode' => 'always',
      'refresh_frequency' => 60,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Default! order type.');

    $order_type = OrderType::load('default');
    $this->assertNotEmpty($order_type);
    $this->assertEquals($edit['label'], $order_type->label());
    $this->assertEmpty($order_type->getNumberPatternId());
    $this->assertEquals($edit['refresh_mode'], $order_type->getRefreshMode());
    $this->assertEquals($edit['refresh_frequency'], $order_type->getRefreshFrequency());
  }

  /**
   * Tests duplicating an order type.
   */
  public function testDuplicate() {
    $this->drupalGet('admin/commerce/config/order-types/default/duplicate');
    $this->assertSession()->fieldValueEquals('label', 'Default');
    $edit = [
      'label' => 'Default2',
      'id' => 'default2',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Saved the Default2 order type.');

    // Confirm that the original order type is unchanged.
    $order_type = OrderType::load('default');
    $this->assertNotEmpty($order_type);
    $this->assertEquals('Default', $order_type->label());
    $this->assertEquals('order_default', $order_type->getNumberPatternId());

    // Confirm that the new order type has the expected data.
    $order_type = OrderType::load('default2');
    $this->assertNotEmpty($order_type);
    $this->assertEquals('Default2', $order_type->label());
    $this->assertEquals('order_default', $order_type->getNumberPatternId());
  }

  /**
   * Tests deleting an order type.
   */
  public function testDelete() {
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->createEntity('commerce_order_type', [
      'id' => 'foo',
      'label' => 'Label for foo',
      'workflow' => 'order_default',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => $order_type->id(),
      'mail' => $this->loggedInUser->getEmail(),
      'store_id' => $this->store,
    ]);

    // Confirm that the type can't be deleted while there's an order.
    $this->drupalGet($order_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('@type is used by 1 order on your site. You cannot remove this order type until you have removed all of the @type orders.', ['@type' => $order_type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Confirm that the delete page is not available when the type is locked.
    $order_type->lock();
    $order_type->save();
    $this->drupalGet($order_type->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals('403');

    // Delete the order, unlock the type, confirm that deletion works.
    $order->delete();
    $order_type->unlock();
    $order_type->save();
    $this->drupalGet($order_type->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the order type @label?', ['@label' => $order_type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $order_type_exists = (bool) OrderType::load($order_type->id());
    $this->assertEmpty($order_type_exists);
  }

  /**
   * Tests order type dependencies.
   */
  public function testOrderTypeDependencies() {
    $this->drupalGet('admin/commerce/config/order-types/default/edit');
    $this->submitForm(['workflow' => 'test_workflow'], t('Save'));

    $order_type = OrderType::load('default');
    $this->assertEquals('test_workflow', $order_type->getWorkflowId());
    $dependencies = $order_type->getDependencies();
    $this->assertArrayHasKey('module', $dependencies);
    $this->assertContains('commerce_order_test', $dependencies['module']);
  }

}
