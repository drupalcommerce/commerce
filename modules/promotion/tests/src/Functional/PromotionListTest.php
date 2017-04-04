<?php

namespace Drupal\Tests\commerce_promotion\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the administrative promotion listing.
 *
 * @group commerce
 * @group commerce_promotion
 */
class PromotionListTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'commerce_cart',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'administer commerce_promotion',
    ] + parent::getAdministratorPermissions();
  }

  /**
   * Tests the display of the status field based on promotion usage and dates.
   */
  public function testPromotionStatusDisplay() {
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Holiday sale',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'commerce_promotion_order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'conditions' => [],
    ]);;

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->linkExists('Holiday sale');
    $this->assertSession()->pageTextContains('Percentage amount off of the order total');
    $this->assertSession()->pageTextContains('∞');
    $this->assertSession()->pageTextContains('Active');

    $promotion->setEnabled(FALSE);
    $promotion->save();

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->pageTextContains('Disabled');

    $promotion->setEnabled(TRUE);
    $promotion->setUsageLimit(500);
    $promotion->save();

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->pageTextContains('Active');
    $this->assertSession()->pageTextNotContains('∞');
    $this->assertSession()->pageTextContains('0 / 500');

    $start = new DrupalDateTime('+1 month');
    $promotion->setStartDate($start);
    $promotion->save();

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->pageTextContains('Starts on ' . $start->format('M, d Y'));

    $promotion->setStartDate(new DrupalDateTime('-3 month'));
    $end = new DrupalDateTime('+3 months');
    $promotion->setEndDate($end);
    $promotion->save();

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->pageTextContains('Ends on ' . $end->format('M, d Y'));

    $end = new DrupalDateTime('-1 months');
    $promotion->setEndDate($end);
    $promotion->save();

    $this->drupalGet($promotion->toUrl('collection'));
    $this->assertSession()->pageTextContains('Inactive');
  }

}
