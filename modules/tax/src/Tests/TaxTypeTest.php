<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Tests\TaxTypeTest.
 */

namespace Drupal\commerce_tax\Tests;
use Drupal\commerce_tax\Entity\TaxType;

/**
 * Tests the commerce_tax_type entity forms.
 *
 * @group commerce
 */
class TaxTypeTest extends TaxTestBase {
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
   * Checks the tax type add form.
   */
  protected function checkTaxTypeAddForm($name) {
    $edit = [
      'id' => $name,
      'name' => 'Test type',
      'roundingMode' => '1',
      'tag' => 'test',
    ];

    $this->assertFalse((bool) TaxType::load($name));
    $this->drupalPostForm('admin/commerce/config/tax/type/add', $edit, t('Save'));
    $this->assertTrue((bool) TaxType::load($name));
  }

  protected function checkTaxTypeEditForm($name) {
    $edit = [
      'id' => $name,
      'name' => 'Test type',
      'roundingMode' => '2',
      'tag' => 'test',
    ];

    $this->assertFalse(TaxType::load($name)->getRoundingMode() === 2);
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/edit', $edit, t('Save'));
    $this->assertTrue(TaxType::load($name)->getRoundingMode() === 2);
  }

  protected function checkTaxTypeDeleteForm($name) {
    $edit = [
      'confirm' => '1',
    ];

    $this->assertTrue((bool) TaxType::load($name));
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/delete', $edit, t('Delete'));
    $this->assertFalse((bool) TaxType::load($name));
  }

}
