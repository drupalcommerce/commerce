<?php

namespace Drupal\Tests\commerce_tax\FunctionalJavascript;

use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the commerce_tax custom plugin.
 *
 * @group commerce
 */
class CustomTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_tax_type',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests the custom plugin.
   */
  public function testTaxTypeCustom() {
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
    $tax_type = $this->createEntity('commerce_tax_type', [
      'id' => 'custom',
      'plugin' => 'custom',
      'label' => 'Custom',
    ]);

    $this->drupalGet($tax_type->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('remove_rate0');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('remove_territory0');
    $this->waitForAjaxToFinish();
    $this->submitForm([], t('Save'));
    $this->assertSession()->pageTextContains('Please add at least one rate.');
    $this->assertSession()->pageTextContains('Please add at least one territory.');

    $this->getSession()->getPage()->selectFieldOption('configuration[custom][display_label]', 'vat');
    $this->getSession()->getPage()->pressButton('Add rate');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('configuration[custom][rates][0][rate][label]', 'Sample rate');
    $this->getSession()->getPage()->fillField('configuration[custom][rates][0][percentage]', '15');
    $this->getSession()->getPage()->pressButton('Add rate');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('configuration[custom][rates][1][rate][label]', 'Sample rate 2');
    $this->getSession()->getPage()->fillField('configuration[custom][rates][1][percentage]', '17.5');

    $this->getSession()->getPage()->pressButton('Add territory');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->selectFieldOption('configuration[custom][territories][0][territory][country_code]', 'FR');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Add territory');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->selectFieldOption('configuration[custom][territories][1][territory][country_code]', 'IT');
    $this->waitForAjaxToFinish();
    $this->submitForm([], t('Save'));
    $this->container->get('entity_type.manager')->getStorage('commerce_tax_type')->resetCache([$tax_type->id()]);
    $tax_type = TaxType::load($tax_type->id());
    $plugin_configuration = $tax_type->getPlugin()->getConfiguration();
    $this->assertEquals('vat', $plugin_configuration['display_label']);
    $this->assertEquals('Sample rate', $plugin_configuration['rates'][0]['label']);
    $this->assertEquals('0.15', $plugin_configuration['rates'][0]['percentage']);
    $this->assertEquals('Sample rate 2', $plugin_configuration['rates'][1]['label']);
    $this->assertEquals('0.175', $plugin_configuration['rates'][1]['percentage']);

    $this->assertEquals('FR', $plugin_configuration['territories'][0]['country_code']);
    $this->assertEquals('IT', $plugin_configuration['territories'][1]['country_code']);
  }

}
