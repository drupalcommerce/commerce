<?php

namespace Drupal\Tests\commerce_product\FunctionalJavascript;

/**
 * Create, edit, delete, and change product attributes.
 *
 * @group commerce
 */
class ProductAttributeJavascriptTest extends ProductWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product_attribute',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests managing product attribute values.
   */
  public function testProductAttributeValues() {
    $attribute = $this->createEntity('commerce_product_attribute', [
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

    // Assert order by weights.
    \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value')->resetCache();
    $attribute_values = array_values($attribute->getValues());
    $this->assertEquals('Cyan', $attribute_values[0]->label());
    $this->assertEquals('Yellow', $attribute_values[1]->label());
    $this->assertEquals('Magenta', $attribute_values[2]->label());
    $this->assertEquals('Black', $attribute_values[3]->label());

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

    // Assert order by weights.
    \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value')->resetCache();
    $attribute_values = array_values($attribute->getValues());
    $this->assertEquals('Cyan', $attribute_values[0]->label());
    $this->assertEquals('Magenta', $attribute_values[1]->label());
    $this->assertEquals('Cornflower Blue', $attribute_values[2]->label());

    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $this->getSession()->getPage()->pressButton('Reset to alphabetical');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Save');

    // Assert order by weights.
    \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value')->resetCache();
    $attribute_values = array_values($attribute->getValues());
    $this->assertEquals('Cornflower Blue', $attribute_values[0]->label());
    $this->assertEquals('Cyan', $attribute_values[1]->label());
    $this->assertEquals('Magenta', $attribute_values[2]->label());

    $this->assertSession()->pageTextContains('Updated the Color product attribute.');
  }

}
