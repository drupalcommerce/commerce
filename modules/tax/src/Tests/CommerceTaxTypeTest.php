<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Tests\CommerceTaxTypeTest.
 */

namespace Drupal\commerce_tax\Tests;

/**
 * Tests the commerce_tax_type entity forms.
 *
 * @group commerce
 */
class CommerceTaxTypeTest extends CommerceTaxTestBase {
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
    $edit = array(
      'id' => $name,
      'name' => 'Test type',
      'roundingMode' => '1',
      'tag' => 'test',
    );

    $this->assertFalse((bool) entity_load('commerce_tax_type', $name));
    $this->drupalPostForm('admin/commerce/config/tax/type/add', $edit, t('Save'));
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
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/edit', $edit, t('Save'));
    $this->assertTrue(entity_load('commerce_tax_type', $name)->getRoundingMode() === 2);
  }

  protected function checkTaxTypeDeleteForm($name) {
    $edit = array(
      'confirm' => '1',
    );

    $this->assertTrue((bool) entity_load('commerce_tax_type', $name));
    $this->drupalPostForm('admin/commerce/config/tax/type/' . $name . '/delete', $edit, t('Delete'));
    $this->assertFalse((bool) entity_load('commerce_tax_type', $name));
  }

}
