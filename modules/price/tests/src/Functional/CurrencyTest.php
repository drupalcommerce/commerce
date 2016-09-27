<?php

namespace Drupal\Tests\commerce_price\Functional;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the currency UI.
 *
 * @group commerce
 */
class CurrencyTest extends CommerceBrowserTestBase {

  /**
   * Tests importing a currency.
   */
  public function testCurrencyImport() {
    $this->drupalGet('admin/commerce/config/currency/import');
    $edit = [
      'currency_codes[]' => ['CHF'],
    ];
    $this->submitForm($edit, 'Import');
    $currency = Currency::load('CHF');
    $this->assertEquals('CHF', $currency->getCurrencyCode());
    $this->assertEquals('Swiss Franc', $currency->getName());
    $this->assertEquals('756', $currency->getNumericCode());
    $this->assertEquals('CHF', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
  }

  /**
   * Tests creating a currency.
   */
  public function testCurrencyCreation() {
    $this->drupalGet('admin/commerce/config/currency');
    $this->getSession()->getPage()->clickLink('Add currency');
    $edit = [
      'name' => 'Test currency',
      'currencyCode' => 'XXX',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->submitForm($edit, 'Save');

    $currency = Currency::load('XXX');
    $this->assertEquals('XXX', $currency->getCurrencyCode());
    $this->assertEquals('Test currency', $currency->getName());
    $this->assertEquals('999', $currency->getNumericCode());
    $this->assertEquals('§', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
    $this->assertSession()->pageTextContains(t("Saved the @name currency.", ['@name' => $edit['name']]));
  }

  /**
   * Tests editing a currency.
   */
  public function testCurrencyEditing() {
    $this->createEntity('commerce_currency', [
      'currencyCode' => 'XXX',
      'name' => 'Test currency',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ]);
    $this->drupalGet('admin/commerce/config/currency/XXX');

    $edit = [
      'name' => 'Test currency2',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->submitForm($edit, 'Save');
    $currency = Currency::load('XXX');
    $this->assertEquals($edit['name'], $currency->getName());
  }

  /**
   * Tests deleting a currency via the admin.
   */
  public function testCurrencyDeletion() {
    $currency = $this->createEntity('commerce_currency', [
      'currencyCode' => 'XXX',
      'name' => 'Test currency',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ]);
    $this->drupalGet('admin/commerce/config/currency/' . $currency->id() . '/delete');
    $this->assertSession()->pageTextContains(t("Are you sure you want to delete the currency @currency?", ['@currency' => $currency->getName()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');

    $currency_exists = (bool) Currency::load($currency->id());
    $this->assertFalse($currency_exists, 'The currency has been deleted from the database.');
  }

}
