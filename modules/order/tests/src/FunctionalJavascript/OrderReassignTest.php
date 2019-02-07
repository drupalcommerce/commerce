<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

/**
 * Tests the commerce_order reassign form.
 *
 * @group commerce
 */
class OrderReassignTest extends OrderWebDriverTestBase {

  /**
   * A test order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_order_type',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $this->order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'order_items' => [$order_item],
      'store_id' => $this->store,
    ]);
  }

  /**
   * Tests the reassign form with a new user.
   */
  public function testReassignToNewUser() {
    $this->drupalGet($this->order->toUrl('reassign-form'));
    $this->getSession()->getPage()->fillField('customer_type', 'new');
    $this->waitForAjaxToFinish();
    $values = [
      'mail' => 'example@example.com',
    ];
    $this->submitForm($values, 'Reassign order');
    $collection_url = $this->order->toUrl('collection', ['absolute' => TRUE]);
    $this->assertSession()->addressEquals($collection_url);
    $this->assertSession()->pageTextContains(t('has been assigned to customer @customer.', [
      '@customer' => 'example@example.com',
    ]));

    $this->order = $this->reloadEntity($this->order);
    $this->assertNotEquals($this->loggedInUser->id(), $this->order->getCustomerId());
    $this->assertEquals('example@example.com', $this->order->getCustomer()->getEmail());
  }

  /**
   * Tests the reassign form with an existing user.
   */
  public function testReassignToExistingUser() {
    $another_user = $this->createUser();
    $this->drupalGet($this->order->toUrl('reassign-form'));
    $this->submitForm(['uid' => $another_user->getAccountName()], 'Reassign order');
    $collection_url = $this->order->toUrl('collection', ['absolute' => TRUE]);
    $this->assertSession()->addressEquals($collection_url);
    $this->assertSession()->pageTextContains(t('has been assigned to customer @customer.', [
      '@customer' => $another_user->getAccountName(),
    ]));

    $this->order = $this->reloadEntity($this->order);
    $this->assertEquals($another_user->id(), $this->order->getCustomerId());
  }

}
