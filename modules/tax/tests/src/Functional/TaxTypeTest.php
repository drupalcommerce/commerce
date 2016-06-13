<?php

namespace Drupal\Tests\commerce_tax\Functional;

use Drupal\commerce_tax\Entity\TaxType;

/**
 * Tests tax types.
 *
 * @group commerce
 */
class TaxTypeTest extends TaxBrowserTestBase {

  /**
   * Tests creating a tax type via a form and programmatically.
   */
  public function testTaxTypeCreation() {
    $zone = $this->createZone();
    $this->drupalGet('admin/commerce/config/tax-types');
    $this->getSession()->getPage()->clickLink('Add a new tax type');
    $id = strtolower($this->randomMachineName(5));
    $edit = [
      'id' => $id,
      'name' => 'Test tax type',
      'zone' => $zone->getId(),
      'compound' => TRUE,
      'displayInclusive' => TRUE,
      'roundingMode' => 4,
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
    $tax_type = TaxType::load($id);
    $this->assertEquals($tax_type->getName(), $edit['name'], 'The new tax type has the correct name.');
    $this->assertTrue($tax_type->isCompound(), 'Tax type is compound.');
    $this->assertTrue($tax_type->isDisplayInclusive(), 'Tax type is display inclusive.');
    $this->assertEquals($tax_type->getZoneId(), $edit['zone'], 'The new tax type has the correct zone ID.');
    $this->assertEquals($tax_type->getRoundingMode(), $edit['roundingMode'], 'Rounding mode 1');

    $zone = $this->createZone();
    $values = [
      'id' => strtolower($this->randomMachineName(5)),
      'name' => $this->randomMachineName(5),
      'zone' => $zone->getId(),
      'compound' => TRUE,
      'displayInclusive' => TRUE,
      'roundingMode' => PHP_ROUND_HALF_UP,
    ];
    $this->createEntity('commerce_tax_type', $values);

    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
    $tax_type = TaxType::load($values['id']);
    $this->assertEquals($tax_type->getName(), $values['name'], 'The new tax type has the correct name.');
    $this->assertEquals($tax_type->getZoneId(), $values['zone'], 'The new tax type has the correct zone ID.');
    $this->assertTrue($tax_type->isCompound(), 'Tax type is compound.');
    $this->assertTrue($tax_type->isDisplayInclusive(), 'Tax type is display inclusive.');
    $this->assertEquals($tax_type->getRoundingMode(), $values['roundingMode'], 'The new tax type has the correct rounding mode.');
  }

  /**
   * Tests editing a tax type via a form.
   */
  public function testTaxTypeEditing() {
    $values = [
      'id' => strtolower($this->randomMachineName(5)),
      'name' => $this->randomMachineName(5),
      'zone' => $this->createZone()->getId(),
      'compound' => FALSE,
      'displayInclusive' => FALSE,
      'roundingMode' => PHP_ROUND_HALF_UP,
    ];
    $tax_type = $this->createEntity('commerce_tax_type', $values);

    $new_zone = $this->createZone();
    $this->drupalGet('admin/commerce/config/tax-types/' . $tax_type->id() . '/edit');
    $edit = [
      'name' => $this->randomMachineName(5),
      'zone' => $new_zone->getId(),
      'compound' => TRUE,
      'displayInclusive' => TRUE,
      'roundingMode' => PHP_ROUND_HALF_DOWN,
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
    $tax_type = TaxType::load($tax_type->id());
    $this->assertEquals($tax_type->getName(), $edit['name'], 'The new tax type has the correct name.');
    $this->assertTrue($tax_type->isCompound(), 'Tax type is compound.');
    $this->assertTrue($tax_type->isDisplayInclusive(), 'Tax type is display inclusive.');
    $this->assertEquals($tax_type->getZoneId(), $edit['zone'], 'The new tax type has the correct zone ID.');
    $this->assertEquals($tax_type->getRoundingMode(), $edit['roundingMode'], 'Rounding mode 1');
  }

  /**
   * Tests deleting a tax type via a form.
   */
  public function testTaxTypeDeletion() {
    $values = [
      'id' => strtolower($this->randomMachineName(5)),
      'name' => $this->randomMachineName(5),
      'zone' => $this->createZone()->getId(),
    ];
    $tax_type = $this->createEntity('commerce_tax_type', $values);

    $this->drupalGet('admin/commerce/config/tax-types/' . $tax_type->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');
    $tax_type_exists = (bool) TaxType::load($tax_type->id());
    $this->assertFalse($tax_type_exists, 'The tax type has been deleted form the database.');
  }

}
