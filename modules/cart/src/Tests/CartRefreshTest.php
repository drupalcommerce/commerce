<?php

/**
 * @file
 * Contains \gDrupal\commerce_cart\Tests\CartRefreshTest.
 */

namespace Drupal\commerce_cart\Tests;

use Drupal\commerce\Tests\CommerceTestBase;
use Drupal\commerce_order\Entity\OrderType;

/**
 * Tests the cart refresh.
 *
 * @group commerce
 */
class CartRefreshTest extends CommerceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer orders',
      'administer order types',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests shopping cart refresh options in order type form.
   */
  function testCartRefreshSettings() {
    $url = 'admin/commerce/config/order-types/default/edit';
    $this->drupalGet($url);
    $this->assertField('refresh_mode', 'Shopping cart refresh mode field found.');
    $this->assertField('refresh_frequency', 'Shopping cart refresh frequency field found.');

    $edit['refresh_mode'] = 'always';
    $edit['refresh_frequency'] = 60;
    $this->drupalPostForm($url, $edit, t('Save'));
    $order_type = OrderType::load('default');
    $refresh_mode = $order_type->getThirdPartySetting('commerce_cart', 'refresh_mode', 'owner_only');
    $refresh_frequency = $order_type->getThirdPartySetting('commerce_cart', 'refresh_frequency', 30);
    $this->assertEqual($refresh_mode, $edit['refresh_mode'], 'The value of the shopping cart refresh mode has been changed.');
    $this->assertEqual($refresh_frequency, $edit['refresh_frequency'], 'The value of the shopping cart refresh frequency has been changed.');
  }

}
