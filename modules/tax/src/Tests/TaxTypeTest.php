<?php

namespace Drupal\commerce_tax\Tests;

use Drupal\commerce_tax\Entity\TaxType;

/**
 * Test case for Commerce Tax Types.
 *
 * @group commerce
 */
class TaxTypeTest extends TaxTestBase {

  /**
   * Test tax type creation form.
   */
  public function testTaxTypeCreationForm() {
    // Define our id.
    $id = 'test_tax_type';
    // Create our zone.
    $zone = $this->createZone();
    // Submit a form with data.
    $this->drupalGet('admin/commerce/config/tax-types');
    $this->clickLink('Add a new tax type');
    $new = [
      'id' => $id,
      'name' => 'Test tax type',
      'zone' => $zone->getId(),
      'compound' => TRUE,
      'displayInclusive' => FALSE,
      'roundingMode' => 4,
    ];
    $this->drupalPostForm(NULL, $new, t('Save'));
    // Load from the database.
    $tax_type = TaxType::load($id);

    // Check the content.
    $this->assertEqual($tax_type->getName(), $new['name'], 'The new tax type has the correct name.');
    $this->assertTrue($tax_type->isCompound(), 'Tax type is compound.');
    $this->assertFalse($tax_type->isDisplayInclusive(), 'Tax type is not displayed inclusive.');
    $this->assertEqual($tax_type->getZoneId(), $zone->getId(), 'The new tax type has the correct zone id.');
    $this->assertEqual($tax_type->getRoundingMode(), $new['roundingMode'], 'Rounding mode 1');

    // Get a new zone.
    $new_zone = $this->createZone();
    // Edit a form with data.
    $this->drupalGet('admin/commerce/config/tax-types/' . $id . '/edit');
    $edit = [
      'name' => 'New Test tax type',
      'zone' => $new_zone->getId(),
      'compound' => FALSE,
      'displayInclusive' => TRUE,
      'roundingMode' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Load from the database.
    $tax_type = TaxType::load($id);

    // Check the content.
    $this->assertEqual($tax_type->getName(), $edit['name'], 'The new tax type has the correct name.');
    $this->assertFalse($tax_type->isCompound(), 'Tax type is compound.');
    $this->assertTrue($tax_type->isDisplayInclusive(), 'Tax type is not displayed inclusive.');
    $this->assertEqual($tax_type->getZoneId(), $new_zone->getId(), 'The new tax type has the correct zone id.');
    $this->assertEqual($tax_type->getRoundingMode(), $edit['roundingMode'], 'Rounding mode 1');
  }

  /**
   * Tests creating a tax type programmatically.
   *
   * @todo: Create kernelTest for this.
   */
  public function testTaxTypeCreation() {
    $zone = $this->createZone();
    // Create a tax type programmatically.
    $values = [
      'id' => strtolower($this->randomMachineName(5)),
      'name' => $this->randomMachineName(5),
      'zone' => $zone->getId(),
      'roundingmode' => 'roundingmode_1',
      'compound' => FALSE,
      'displayInclusive' => FALSE,
    ];
    $tax_type = $this->createEntity('commerce_tax_type', $values);
    // Reload tax type from database.
    $tax_type = TaxType::load($values['id']);

    // Check methods.
    $this->assertEqual($tax_type->getName(), $values['name'], 'The new tax type has the correct name.');
    $this->assertEqual($tax_type->getZoneId(), $zone->getId(), 'The new tax type has the correct zone id.');
    $this->assertFalse($tax_type->isCompound(), 'Tax type is not compound.');
    $this->assertFalse($tax_type->isDisplayInclusive(), 'Tax type is not displayed inclusive.');
  }

}
