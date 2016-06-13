<?php

namespace Drupal\Tests\commerce_product\FunctionalJavascript;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;

/**
 * Create, edit, delete, and change product attributes.
 *
 * @group commerce
 */
class ProductAttributeJavascriptTest extends ProductBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer product attributes',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests managing product attribute values.
   */
  public function testProductAttributeValues() {
    $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    // Add three extra options.
    $this->getSession()->getPage()->fillField('values[0][entity][name][0][value]', 'Cyan');
    $this->getSession()->getPage()->pressButton('Add value');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('values[1][entity][name][0][value]', 'Yellow');
    $this->getSession()->getPage()->pressButton('Add value');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('values[2][entity][name][0][value]', 'Magenta');
    $this->getSession()->getPage()->pressButton('Add value');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('values[3][entity][name][0][value]', 'Black');

    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Updated the Color product attribute.');

    $attribute = ProductAttribute::load('color');
    $attribute_values = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
    foreach ($attribute->getValues() as $value) {
      $attribute_values[] = $value->label();
    }
    $this->assertTrue($attribute_values[0] == 'Cyan');
    $this->assertTrue($attribute_values[1] == 'Yellow');
    $this->assertTrue($attribute_values[2] == 'Magenta');
    $this->assertTrue($attribute_values[3] == 'Black');

    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $this->getSession()->getPage()->pressButton('remove_value1');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('remove_value3');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Add value');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('values[3][entity][name][0][value]', 'Cornflower Blue');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Updated the Color product attribute.');

    $attribute = ProductAttribute::load('color');
    $attribute_values = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
    foreach ($attribute->getValues() as $value) {
      $attribute_values[] = $value->label();
    }
    $this->assertTrue($attribute_values[0] == 'Cyan');
    $this->assertTrue($attribute_values[1] == 'Magenta');
    $this->assertTrue($attribute_values[2] == 'Cornflower Blue');

    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $this->getSession()->getPage()->pressButton('Reset to alphabetical');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Updated the Color product attribute.');
    // @todo Confirm weights once $attribute->getValues() starts using them.
  }

}
