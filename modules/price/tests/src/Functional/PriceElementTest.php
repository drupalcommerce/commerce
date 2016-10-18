<?php

namespace Drupal\Tests\commerce_price\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the price element.
 *
 * @group commerce
 */
class PriceElementTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_price_test',
    'language',
  ];

  /**
   * Tests the element with a single currency.
   */
  public function testSingleCurrency() {
    $this->drupalGet('/commerce_price_test/price_test_form');
    $this->assertSession()->fieldExists('amount[number]');
    // Default value.
    $this->assertSession()->fieldValueEquals('amount[number]', '99.99');

    // Invalid submit.
    $edit = [
      'amount[number]' => 'invalid',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount is not numeric.');

    // Valid submit.
    $edit = [
      'amount[number]' => '10.99',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99" and the currency code is "USD".');
  }

  /**
   * Tests the element with multiple currencies.
   */
  public function testMultipleCurrency() {
    $this->container->get('commerce_price.currency_importer')->import('EUR');

    $this->drupalGet('/commerce_price_test/price_test_form');
    $this->assertSession()->fieldExists('amount[number]');
    $this->assertSession()->fieldExists('amount[currency_code]');
    // Default value.
    $this->assertSession()->fieldValueEquals('amount[number]', '99.99');
    $this->assertSession()->optionExists('amount[currency_code]', 'EUR');
    $element = $this->assertSession()->optionExists('amount[currency_code]', 'USD');
    $this->assertTrue($element->isSelected());

    // Invalid submit.
    $edit = [
      'amount[number]' => 'invalid',
      'amount[currency_code]' => 'USD',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount is not numeric.');

    // Valid submit.
    $edit = [
      'amount[number]' => '10.99',
      'amount[currency_code]' => 'EUR',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99" and the currency code is "EUR".');
  }

  /**
   * Tests the element with a non-English number format.
   */
  public function testLocalFormat() {
    // French uses a comma as a decimal separator.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    $this->drupalGet('/commerce_price_test/price_test_form');
    $this->assertSession()->fieldExists('amount[number]');
    // Default value.
    $this->assertSession()->fieldValueEquals('amount[number]', '99,99');

    // Valid submit.
    $edit = [
      'amount[number]' => '10,99',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99" and the currency code is "USD".');
  }

}
