<?php

namespace Drupal\Tests\commerce_log\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the order activity on order admin view.
 *
 * @group commerce
 */
class OrderAdminActivityTest extends CommerceBrowserTestBase {

  /**
   * The order to test against.
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
    'commerce_order',
    'commerce_log',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $this->order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'store_id' => $this->store,
    ]);
  }

  /**
   * Tests order activity on Order admin view.
   */
  public function testOrderAdminActivity() {
    $this->drupalGet($this->order->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains(t('Order activity'));
    $transition = $this->order->getState()->getTransitions();
    $this->order->getState()->applyTransition($transition['cancel']);
    $this->order->save();
    $this->drupalGet($this->order->toUrl());
    $this->assertSession()->pageTextContains(t('Order activity'));
    $this->assertSession()->pageTextContains(t('The order was canceled.'));
  }

}
