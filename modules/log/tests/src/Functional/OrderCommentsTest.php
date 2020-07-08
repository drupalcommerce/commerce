<?php

namespace Drupal\Tests\commerce_log\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Tests adding order comments.
 *
 * @group commerce
 * @group commerce_log
 */
class OrderCommentsTest extends OrderBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_log',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_order',
      'administer commerce_order_type',
      'access commerce_order overview',
      'add commerce_log commerce_order admin comment',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests adding an order comment.
   */
  public function testAddOrderComment() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
    ]);

    $test_comment = sprintf('Urgent order for %s!', $this->loggedInUser->getEmail());

    $this->drupalGet($order->toUrl('canonical'));
    $this->assertSession()->pageTextContains('Comment on this order');
    $this->assertSession()->pageTextContains('Your comment will only be visible to users who have access to the activity log.');
    $this->getSession()->getPage()->fillField('Comment', $test_comment);
    $this->getSession()->getPage()->pressButton('Add comment');
    $this->assertSession()->pageTextContainsOnce($test_comment);

    $test_filtered_comment = '<script>alert("hello")</script> test comment';
    $this->getSession()->getPage()->fillField('Comment', $test_filtered_comment);
    $this->getSession()->getPage()->pressButton('Add comment');

    $this->assertSession()->pageTextNotContains($test_filtered_comment);
    $this->assertSession()->pageTextContains(Html::escape($test_filtered_comment));
  }

}
