<?php

namespace Drupal\Tests\commerce_price\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the number element.
 *
 * @group commerce
 */
class NumberElementTest extends CommerceBrowserTestBase {

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
   * Tests the element with valid and invalid input.
   */
  public function testInput() {
    $this->drupalGet('/commerce_price_test/number_test_form');
    $this->assertSession()->fieldExists('number');
    // Default value.
    $this->assertSession()->fieldValueEquals('number', '99.99');

    // Not a number.
    $edit = [
      'number' => 'invalid',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount must be a number.');
    // Number too low.
    $edit = [
      'number' => '1',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount must be higher than or equal to 2.');
    // Number too high.
    $edit = [
      'number' => '101',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Amount must be lower than or equal to 100.');

    // Valid submit. Ensure that the value is trimmed.
    $edit = [
      'number' => '10.99 ',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99".');
  }

  /**
   * Tests the element with a non-English number format.
   */
  public function testLocalFormat() {
    // French uses a comma as a decimal separator.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    $this->drupalGet('/commerce_price_test/number_test_form');
    $this->assertSession()->fieldExists('number');
    // Default value.
    $this->assertSession()->fieldValueEquals('number', '99,99');

    // Valid submit.
    $edit = [
      'number' => '10,99',
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('The number is "10.99".');
  }

}
