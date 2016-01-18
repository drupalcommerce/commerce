<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Tests\CurrencyTest.
 */

namespace Drupal\commerce_price\Tests;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce\Tests\CommerceTestBase;

/**
 * Tests the currency UI.
 *
 * @group commerce
 */
class CurrencyTest extends CommerceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'system',
    'user',
    'commerce',
    'commerce_store',
    'commerce_price',
  ];

  /**
   * Tests importing a currency.
   */
  function testCurrencyImport() {
    $this->drupalGet('admin/commerce/config/currency/import');
    $edit = [
      'currency_code' => 'CHF',
    ];
    $this->drupalPostForm(NULL, $edit, t('Import'));
    $currency = Currency::load('CHF');
    $this->assertEqual($currency->getCurrencyCode(), 'CHF');
    $this->assertEqual($currency->getName(), 'Swiss Franc');
    $this->assertEqual($currency->getNumericCode(), '756');
    $this->assertEqual($currency->getSymbol(), 'CHF');
    $this->assertEqual($currency->getFractionDigits(), '2');
    $this->assertText(t('Imported the @name currency.', ['@name' => $currency->getName()]), 'Currency import success message is visible.');
  }


  /**
   * Tests creating a currency.
   */
  function testCurrencyCreation() {
    $this->drupalGet('admin/commerce/config/currency');
    $this->clickLink('Add a new currency');
    $edit = [
      'name' => 'Test currency',
      'currencyCode' => 'XXX',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $currency = Currency::load('XXX');
    $this->assertEqual($currency->getCurrencyCode(), 'XXX');
    $this->assertEqual($currency->getName(), 'Test currency');
    $this->assertEqual($currency->getNumericCode(), '999');
    $this->assertEqual($currency->getSymbol(), '§');
    $this->assertEqual($currency->getFractionDigits(), '2');
    $this->assertText(t("Saved the @name currency.", ['@name' => $edit['name']]), "Currency creation success message is visible.");
  }

  /**
   * Tests editing a currency.
   */
  function testCurrencyEditing() {
    $this->createEntity('commerce_currency', [
      'currencyCode' => 'XXX',
      'name' => 'Test currency',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ]);

    $edit = [
      'name' => 'Test currency2',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ];
    $this->drupalPostForm('admin/commerce/config/currency/XXX', $edit, t('Save'));
    $currency = Currency::load('XXX');
    $this->assertEqual($currency->getName(), $edit['name'], 'The name of the currency has been changed.');
  }

  /**
   * Tests deleting a currency via the admin.
   */
  function testCurrencyDeletion() {
    $currency = $this->createEntity('commerce_currency', [
      'currencyCode' => 'XXX',
      'name' => 'Test currency',
      'numericCode' => 999,
      'symbol' => '§',
      'fractionDigits' => 2,
    ]);
    $this->drupalGet('admin/commerce/config/currency/' . $currency->id() . '/delete');
    $this->assertText(t("Are you sure you want to delete the currency @currency?", ['@currency' => $currency->getName()]), "Commerce Currency deletion confirmation text is showing");
    $this->assertText(t('This action cannot be undone.'), 'The currency deletion confirmation form is available');
    $this->drupalPostForm(NULL, NULL, t('Delete'));

    $currency_exists = (bool) Currency::load($currency->id());
    $this->assertFalse($currency_exists, 'The currency has been deleted from the database.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer stores',
    ], parent::getAdministratorPermissions());
  }

}
