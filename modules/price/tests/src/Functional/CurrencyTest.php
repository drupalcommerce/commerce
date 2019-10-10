<?php

namespace Drupal\Tests\commerce_price\Functional;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests the currency UI.
 *
 * @group commerce
 */
class CurrencyTest extends CommerceBrowserTestBase {

  /**
   * Tests the initial currency creation.
   */
  public function testInitialCurrency() {
    // We are expecting commerce_price_install() to import 'USD'.
    $currency = Currency::load('USD');
    $this->assertNotEmpty($currency);
  }

  /**
   * Tests importing a currency.
   */
  public function testCurrencyImport() {
    $this->drupalGet('admin/commerce/config/currencies/add');
    $edit = [
      'currency_codes[]' => ['CHF'],
    ];
    $this->submitForm($edit, 'Add');

    $url = Url::fromRoute('entity.commerce_currency.collection');
    $this->assertEquals($this->getUrl(), $this->getAbsoluteUrl($url->toString()));

    $currency = Currency::load('CHF');
    $this->assertEquals('CHF', $currency->getCurrencyCode());
    $this->assertEquals('Swiss Franc', $currency->getName());
    $this->assertEquals('756', $currency->getNumericCode());
    $this->assertEquals('CHF', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
  }

  /**
   * Tests adding a currency.
   */
  public function testCurrencyCreation() {
    $this->drupalGet('admin/commerce/config/currencies');
    $this->getSession()->getPage()->clickLink('Add custom currency');
    $edit = [
      'name' => 'Test currency',
      'currencyCode' => 'XXX',
      'numericCode' => '999',
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains(t('Saved the @name currency.', ['@name' => $edit['name']]));
    $currency = Currency::load('XXX');
    $this->assertEquals('XXX', $currency->getCurrencyCode());
    $this->assertEquals('Test currency', $currency->getName());
    $this->assertEquals('999', $currency->getNumericCode());
    $this->assertEquals('§', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
  }

  /**
   * Tests adding a cryptocurrency.
   */
  public function testCryptoCurrencyCreation() {
    $this->drupalGet('admin/commerce/config/currencies');
    $this->getSession()->getPage()->clickLink('Add custom currency');
    $edit = [
      'name' => 'Test cryptocurrency',
      'currencyCode' => 'XXXX',
      'numericCode' => '000',
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains(t('Saved the @name currency.', ['@name' => $edit['name']]));
    $currency = Currency::load('XXXX');
    $this->assertEquals('XXXX', $currency->getCurrencyCode());
    $this->assertEquals('Test cryptocurrency', $currency->getName());
    $this->assertEquals('000', $currency->getNumericCode());
    $this->assertEquals('§', $currency->getSymbol());
    $this->assertEquals('2', $currency->getFractionDigits());
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
    $this->drupalGet('admin/commerce/config/currencies/XXX');

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
    $this->drupalGet('admin/commerce/config/currencies/' . $currency->id() . '/delete');
    $this->assertSession()->pageTextContains(t("Are you sure you want to delete the currency @currency?", ['@currency' => $currency->getName()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');

    $currency_exists = (bool) Currency::load($currency->id());
    $this->assertEmpty($currency_exists, 'The currency has been deleted from the database.');
  }

}
