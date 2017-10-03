<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the admin UI for promotions.
 *
 * @group commerce
 */
class PromotionTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'path',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_promotion',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a promotion.
   */
  public function testCreatePromotion() {
    $this->drupalGet('admin/commerce/promotions');
    $this->getSession()->getPage()->clickLink('Add promotion');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('name[0][value]');
    $name = $this->randomMachineName(8);
    $this->getSession()->getPage()->fillField('name[0][value]', $name);
    $this->getSession()->getPage()->selectFieldOption('offer[0][target_plugin_id]', 'order_item_percentage_off');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_configuration][order_item_percentage_off][percentage]', '10.0');

    // Change, assert any values reset.
    $this->getSession()->getPage()->selectFieldOption('offer[0][target_plugin_id]', 'order_percentage_off');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldValueNotEquals('offer[0][target_plugin_configuration][order_percentage_off][percentage]', '10.0');
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_configuration][order_percentage_off][percentage]', '10.0');

    // Confirm the integrity of the conditions UI.
    foreach (['order', 'product', 'customer'] as $condition_group) {
      $tab_matches = $this->xpath('//a[@href="#edit-conditions-form-' . $condition_group . '"]');
      $this->assertNotEmpty($tab_matches);
    }
    $vertical_tab_elements = $this->xpath('//a[@href="#edit-conditions-form-order"]');
    $vertical_tab_element = reset($vertical_tab_elements);
    $vertical_tab_element->click();
    $this->getSession()->getPage()->checkField('Limit by total price');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('conditions[form][order][order_total_price][configuration][form][amount][number]', '50.00');

    // Confirm that the usage limit widget works properly.
    $this->getSession()->getPage()->hasCheckedField(' Unlimited');
    $usage_limit_xpath = '//input[@type="number" and @name="usage_limit[0][usage_limit]"]';
    $this->assertFalse($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
    $this->getSession()->getPage()->checkField('Limited number of uses');
    $this->assertTrue($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
    $this->getSession()->getPage()->fillField('usage_limit[0][usage_limit]', '99');

    $this->submitForm([], t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name promotion.");
    $promotion_count = $this->getSession()->getPage()->find('xpath', '//table/tbody/tr/td[text()="' . $name . '"]');
    $this->assertEquals(count($promotion_count), 1, 'promotions exists in the table.');

    $promotion = Promotion::load(1);
    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['percentage']);

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $condition_field */
    $condition_field = $promotion->get('conditions')->first();
    $this->assertEquals('50.00', $condition_field->target_plugin_configuration['amount']['number']);

    $this->assertEquals('99', $promotion->getUsageLimit());
    $this->drupalGet($promotion->toUrl('edit-form'));
    $this->getSession()->getPage()->hasCheckedField('Limited number of uses');
    $this->assertTrue($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
  }

  /**
   * Tests creating a promotion with an end date.
   */
  public function testCreatePromotionWithEndDate() {
    $this->drupalGet('admin/commerce/promotions');
    $this->getSession()->getPage()->clickLink('Add promotion');
    $this->drupalGet('promotion/add');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('name[0][value]');

    $this->getSession()->getPage()->fillField('offer[0][target_plugin_id]', 'order_percentage_off');
    $this->waitForAjaxToFinish();

    $name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $name,
      'offer[0][target_plugin_configuration][order_percentage_off][percentage]' => '10.0',
    ];

    // Set an end date.
    $this->getSession()->getPage()->checkField('end_date[0][has_value]');
    $edit['end_date[0][container][value][date]'] = date("Y") + 1 . '-01-01';

    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name promotion.");
    $promotion_count = $this->getSession()->getPage()->find('xpath', '//table/tbody/tr/td[text()="' . $name . '"]');
    $this->assertEquals(count($promotion_count), 1, 'promotions exists in the table.');

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = Promotion::load(1)->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['percentage']);
  }

  /**
   * Tests editing a promotion.
   */
  public function testEditPromotion() {
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => $this->randomMachineName(8),
      'status' => TRUE,
      'offer' => [
        'target_plugin_id' => 'order_item_percentage_off',
        'target_plugin_configuration' => [
          'percentage' => '0.10',
        ],
      ],
      'conditions' => [
        [
          'target_plugin_id' => 'order_total_price',
          'target_plugin_configuration' => [
            'amount' => [
              'number' => '9.10',
              'currency_code' => 'USD',
            ],
          ],
        ],
      ],
    ]);

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['percentage']);

    $this->drupalGet($promotion->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Restricted');
    $this->assertSession()->checkboxChecked('Limit by total price');
    $this->assertSession()->fieldValueEquals('conditions[form][order][order_total_price][configuration][form][amount][number]', '9.10');

    $new_promotion_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_promotion_name,
      'offer[0][target_plugin_configuration][order_item_percentage_off][percentage]' => '20',
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$promotion->id()]);
    $promotion_changed = Promotion::load($promotion->id());
    $this->assertEquals($new_promotion_name, $promotion_changed->getName(), 'The promotion name successfully updated.');

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion_changed->get('offer')->first();
    $this->assertEquals('0.20', $offer_field->target_plugin_configuration['percentage']);
  }

  /**
   * Tests deleting a promotion.
   */
  public function testDeletePromotion() {
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($promotion->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$promotion->id()]);
    $promotion_exists = (bool) Promotion::load($promotion->id());
    $this->assertEmpty($promotion_exists, 'The new promotion has been deleted from the database using UI.');
  }

}
