<?php

namespace Drupal\Tests\commerce_price\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the price element with AJAX.
 *
 * @group commerce
 */
class AjaxPriceElementTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_price_test',
  ];

  /**
   * Tests the price element with AJAX.
   */
  public function testAjaxPrice() {
    $this->container->get('commerce_price.currency_importer')->import('EUR');
    $this->drupalGet('/commerce_price_test/ajax_price_test_form');
    $this->assertSession()->fieldExists('amount[number]');

    // Default value.
    $this->assertSession()->fieldValueEquals('amount[number]', '99.99');
    $this->assertSession()->pageTextNotContains('Ajax successful');

    // Change the amount[currency_code] field to trigger AJAX request.
    $this->getSession()->getPage()->findField('amount[currency_code]')->selectOption('EUR');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('AJAX successful: amount[number]');
    $this->assertSession()->pageTextContains('AJAX successful: amount[currency_code]');

    // Blur the amount[number] field to trigger AJAX request.
    $this->getSession()->getPage()->findField('amount[number]')->blur();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('AJAX successful: amount[number]');
    $this->assertSession()->pageTextNotContains('AJAX successful: amount[currency_code]');
  }

}
