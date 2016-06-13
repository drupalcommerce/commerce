<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_order\Entity\OrderType;

/**
 * Tests the cart refresh.
 *
 * @group commerce
 */
class CartRefreshTest extends CommerceBrowserTestBase {

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
  public function testCartRefreshSettings() {
    $url = 'admin/commerce/config/order-types/default/edit';
    $this->drupalGet($url);
    $this->assertSession()->fieldExists('commerce_cart[refresh_mode]');
    $this->assertSession()->fieldExists('commerce_cart[refresh_frequency]');

    $edit['commerce_cart[refresh_mode]'] = 'always';
    $edit['commerce_cart[refresh_frequency]'] = 60;
    $this->submitForm($edit, t('Save'));
    $order_type = OrderType::load('default');
    $refresh_mode = $order_type->getThirdPartySetting('commerce_cart', 'refresh_mode', 'owner_only');
    $refresh_frequency = $order_type->getThirdPartySetting('commerce_cart', 'refresh_frequency', 30);
    $this->assertEquals($refresh_mode, $edit['commerce_cart[refresh_mode]'], 'The value of the shopping cart refresh mode has been changed.');
    $this->assertEquals($refresh_frequency, $edit['commerce_cart[refresh_frequency]'], 'The value of the shopping cart refresh frequency has been changed.');
  }

}
