<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Tests\CommerceTaxTypeTest.
 */

namespace Drupal\commerce_tax\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests the commerce_tax_type entity forms.
 *
 * @group commerce
 */
class CommerceTaxTypeTest extends WebTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   */
  public static $modules = array('commerce_tax');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);
  }

  /**
   * Checks that the default tax types are correctly imported.
   */
  public function testDefaultTaxTypes() {
    $this->checkDefaultAdminPage();
    $this->checkDefaultConfig();
  }

  /**
   * Checks that the tax types forms exist.
   */
  public function testTaxTypeForms() {
    $name = 'test_type';
    $this->checkTaxTypeAddForm($name);
    $this->checkTaxTypeEditForm($name);
    $this->checkTaxTypeDeleteForm($name);
  }

  /**
   * Checks that the default tax types exist on the admin page.
   */
  protected function checkDefaultAdminPage() {
    $this->drupalGet('admin/commerce/config/tax/type');

    $machine_names = array('sales_tax', 'vat');
    foreach ($machine_names as $i => $name) {
      $this->assertText($name, $name . ' exists');
    }

    $names = array('Sales tax', 'VAT');
    foreach ($names as $name) {
      $this->assertText($name, $name . ' exists');
    }

    $tags = array('sales', 'vat');
    foreach ($tags as $tag) {
      $this->assertText($tag, $tag . ' exists');
    }
  }

  /**
   * Checks that the default tax types exist in the config.
   */
  protected function checkDefaultConfig() {
    $this->assertTrue((bool) entity_load('commerce_tax_type', 'sales_tax'));
    $this->assertTrue((bool) entity_load('commerce_tax_type', 'vat'));
    $this->assertTrue(entity_load('commerce_tax_type', 'sales_tax')->getName() === $this->t('Sales tax'));
  }

  /**
   * Checks the tax type add form.
   */
  protected function checkTaxTypeAddForm($name) {
    $edit = array(
      'id' => $name,
      'name' => 'Test type',
      'roundingMode' => '1',
      'tag' => 'test',
    );

    $this->assertFalse((bool) entity_load('commerce_tax_type', $name));
    $this->drupalPostForm('admin/commerce/config/tax/type/add', $edit, $this->t('Save'));
    $this->assertTrue((bool) entity_load('commerce_tax_type', $name));
  }

  protected function checkTaxTypeEditForm($name) {
    $edit = array(
      'id' => $name,
      'name' => 'Test type',
      'roundingMode' => '2',
      'tag' => 'test',
    );

    $this->assertFalse(entity_load('commerce_tax_type', $name)->getRoundingMode() === 2);
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/edit', $edit, $this->t('Save'));
    $this->assertTrue(entity_load('commerce_tax_type', $name)->getRoundingMode() === 2);
  }

  protected function checkTaxTypeDeleteForm($name) {
    $edit = array(
      'confirm' => '1',
    );

    $this->assertTrue((bool) entity_load('commerce_tax_type', $name));
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/delete', $edit, $this->t('Delete'));
    $this->assertFalse((bool) entity_load('commerce_tax_type', $name));
  }

}
