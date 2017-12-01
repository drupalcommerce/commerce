<?php

namespace Drupal\Tests\commerce_price\Functional;

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
    $this->assertSession()->pageTextContains('Amount must be a number.');

    // Valid submit.
    $edit = [
      'amount[number]' => '10.99',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99" and the currency code is "USD".');

    // Ensure that the form titles are displayed as expected.
    $elements = $this->xpath('//input[@id="edit-amount-hidden-title-number"]/preceding-sibling::label[@for="edit-amount-hidden-title-number" and contains(@class, "visually-hidden")]');
    $this->assertTrue(isset($elements[0]), 'Label preceding field and label class is visually-hidden.');

    $elements = $this->xpath('//input[@id="edit-amount-number"]/preceding-sibling::label[@for="edit-amount-number" and not(contains(@class, "visually-hidden"))]');
    $this->assertTrue(isset($elements[0]), 'Label preceding field and label class is not visually visually-hidden.');
  }

  /**
   * Tests the element with multiple currencies.
   */
  public function testMultipleCurrency() {
    $this->container->get('commerce_price.currency_importer')->import('EUR');
    $this->container->get('commerce_price.currency_importer')->import('CHF');

    $this->drupalGet('/commerce_price_test/price_test_form');
    $this->assertSession()->fieldExists('amount[number]');
    $this->assertSession()->fieldExists('amount[currency_code]');
    // Default value.
    $this->assertSession()->fieldValueEquals('amount[number]', '99.99');
    $this->assertSession()->optionExists('amount[currency_code]', 'EUR');
    $element = $this->assertSession()->optionExists('amount[currency_code]', 'USD');
    $this->assertNotEmpty($element->isSelected());
    // CHF is not in #availabe_currencies so it must not be present.
    $this->assertSession()->optionNotExists('amount[currency_code]', 'CHF');

    // Invalid submit.
    $edit = [
      'amount[number]' => 'invalid',
      'amount[currency_code]' => 'USD',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount must be a number.');

    // Valid submit.
    $edit = [
      'amount[number]' => '10.99',
      'amount[currency_code]' => 'EUR',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99" and the currency code is "EUR".');
  }

}
