<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the admin UI for promotions.
 *
 * @group commerce
 */
class PromotionTest extends CommerceWebDriverTestBase {

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
    $this->assertSession()->fieldExists('display_name[0][value]');
    $name = $this->randomMachineName(8);
    $this->getSession()->getPage()->fillField('name[0][value]', $name);
    $this->getSession()->getPage()->fillField('display_name[0][value]', 'Discount');
    $this->getSession()->getPage()->selectFieldOption('offer[0][target_plugin_id]', 'order_item_percentage_off');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_configuration][order_item_percentage_off][percentage]', '10.0');

    // Change, assert any values reset.
    $this->getSession()->getPage()->selectFieldOption('offer[0][target_plugin_id]', 'order_percentage_off');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueNotEquals('offer[0][target_plugin_configuration][order_percentage_off][percentage]', '10.0');
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_configuration][order_percentage_off][percentage]', '10.0');

    // Confirm the integrity of the conditions UI.
    foreach (['order', 'products', 'customer'] as $condition_group) {
      $tab_matches = $this->xpath('//a[@href="#edit-conditions-form-' . $condition_group . '"]');
      $this->assertNotEmpty($tab_matches);
    }
    $vertical_tab_elements = $this->xpath('//a[@href="#edit-conditions-form-order"]');
    $vertical_tab_element = reset($vertical_tab_elements);
    $vertical_tab_element->click();
    $this->getSession()->getPage()->checkField('Current order total');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('conditions[form][order][order_total_price][configuration][form][amount][number]', '50.00');

    // Confirm that the usage limit widget works properly.
    $this->getSession()->getPage()->hasCheckedField(' Unlimited');
    $usage_limit_xpath = '//input[@type="number" and @name="usage_limit[0][usage_limit]"]';
    $this->assertFalse($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
    // Select 'Limited number of uses'.
    $this->getSession()->getPage()->selectFieldOption('usage_limit[0][limit]', '1');
    $this->assertTrue($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
    $this->getSession()->getPage()->fillField('usage_limit[0][usage_limit]', '99');

    $this->setRawFieldValue('start_date[0][value][date]', '2019-11-29');
    $this->setRawFieldValue('start_date[0][value][time]', '10:30:00');
    $this->submitForm([], t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name promotion.");
    $rows = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr/td[text()="' . $name . '"]');
    $this->assertCount(1, $rows);

    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = Promotion::load(1);
    $this->assertEquals($name, $promotion->getName());
    $this->assertEquals('Discount', $promotion->getDisplayName());
    $offer = $promotion->getOffer();
    $this->assertEquals('0.10', $offer->getConfiguration()['percentage']);
    $conditions = $promotion->getConditions();
    $condition = reset($conditions);
    $this->assertEquals('50.00', $condition->getConfiguration()['amount']['number']);
    $this->assertEquals('99', $promotion->getUsageLimit());
    $this->assertEquals('2019-11-29 10:30:00', $promotion->getStartDate()->format('Y-m-d H:i:s'));
    $this->assertNull($promotion->getEndDate());

    $this->drupalGet($promotion->toUrl('edit-form'));
    $this->getSession()->getPage()->hasCheckedField('Limited number of uses');
    $this->assertTrue($this->getSession()->getDriver()->isVisible($usage_limit_xpath));
  }

  /**
   * Tests creating a promotion using the "Save and add coupons" button.
   */
  public function testCreatePromotionWithSaveAndAddCoupons() {
    $this->drupalGet('admin/commerce/promotions');
    $this->getSession()->getPage()->clickLink('Add promotion');

    $name = $this->randomString();
    $this->getSession()->getPage()->fillField('name[0][value]', $name);
    $this->getSession()->getPage()->selectFieldOption('offer[0][target_plugin_id]', 'order_item_fixed_amount_off');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_configuration][order_item_fixed_amount_off][amount][number]', '10.00');
    $this->submitForm([], t('Save and add coupons'));
    $this->assertSession()->pageTextContains("Saved the $name promotion.");

    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = Promotion::load(1);
    $offer = $promotion->getOffer();
    $this->assertEquals('order_item_fixed_amount_off', $offer->getPluginId());
    $this->assertEquals('10.00', $offer->getConfiguration()['amount']['number']);
  }

  /**
   * Tests creating a promotion with an end date.
   */
  public function testCreatePromotionWithEndDate() {
    $this->drupalGet('admin/commerce/promotions');
    $this->getSession()->getPage()->clickLink('Add promotion');
    $this->drupalGet('promotion/add');

    $this->assertSession()->fieldExists('name[0][value]');
    $this->getSession()->getPage()->fillField('offer[0][target_plugin_id]', 'order_percentage_off');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $end_date = new DrupalDateTime('now', 'UTC');
    $end_date = $end_date->modify('+1 month');
    $name = $this->randomMachineName(8);
    $this->getSession()->getPage()->checkField('end_date[0][has_value]');
    $this->setRawFieldValue('end_date[0][container][value][date]', $end_date->format('Y-m-d'));
    $this->setRawFieldValue('end_date[0][container][value][time]', $end_date->format('H:i:s'));
    $edit = [
      'name[0][value]' => $name,
      'offer[0][target_plugin_configuration][order_percentage_off][percentage]' => '10.0',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name promotion.");

    $rows = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr/td[text()="' . $name . '"]');
    $this->assertCount(1, $rows);
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = Promotion::load(1);
    $offer = $promotion->getOffer();
    $this->assertEquals('0.10', $offer->getConfiguration()['percentage']);
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $this->assertEquals($end_date->format($storage_format), $promotion->getEndDate()->format($storage_format));
  }

  /**
   * Tests editing a promotion.
   */
  public function testEditPromotion() {
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => '10% off',
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
      'start_date' => '2019-10-07T13:37:00',
    ]);

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer_field */
    $offer_field = $promotion->get('offer')->first();
    $this->assertEquals('0.10', $offer_field->target_plugin_configuration['percentage']);

    $this->drupalGet($promotion->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Restricted');
    $this->assertSession()->checkboxChecked('Current order total');
    $this->assertSession()->fieldValueEquals('conditions[form][order][order_total_price][configuration][form][amount][number]', '9.10');

    $this->setRawFieldValue('start_date[0][value][time]', '14:15:13');
    $edit = [
      'name[0][value]' => '20% off',
      'offer[0][target_plugin_configuration][order_item_percentage_off][percentage]' => '20',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the 20% off promotion.');

    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = $this->reloadEntity($promotion);
    $this->assertEquals('20% off', $promotion->getName());
    $offer = $promotion->getOffer();
    $this->assertEquals('0.20', $offer->getConfiguration()['percentage']);
    $this->assertEquals('2019-10-07 14:15:13', $promotion->getStartDate()->format('Y-m-d H:i:s'));
  }

  /**
   * Tests duplicating a promotion.
   */
  public function testDuplicatePromotion() {
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => '10% off',
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

    $this->drupalGet($promotion->toUrl('duplicate-form'));
    // Check the integrity of the form.
    $this->assertSession()->fieldValueEquals('name[0][value]', '10% off');
    $this->assertSession()->fieldValueEquals('offer[0][target_plugin_id]', 'order_item_percentage_off');
    $this->assertSession()->fieldValueEquals('offer[0][target_plugin_configuration][order_item_percentage_off][percentage]', '10');
    $this->assertSession()->pageTextContains('Restricted');
    $this->assertSession()->checkboxChecked('Current order total');
    $this->assertSession()->fieldValueEquals('conditions[form][order][order_total_price][configuration][form][amount][number]', '9.10');

    $edit = [
      'name[0][value]' => '20% off',
      'offer[0][target_plugin_configuration][order_item_percentage_off][percentage]' => '20',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the 20% off promotion.');

    // Confirm that the original promotion is unchanged.
    $promotion = $this->reloadEntity($promotion);
    $this->assertNotEmpty($promotion);
    $this->assertEquals('10% off', $promotion->label());

    // Confirm that the new promotion has the expected data.
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = Promotion::load($promotion->id() + 1);
    $this->assertNotEmpty($promotion);
    $this->assertEquals('20% off', $promotion->label());
    $offer = $promotion->getOffer();
    $this->assertEquals('0.20', $offer->getConfiguration()['percentage']);
  }

  /**
   * Tests deleting a promotion.
   */
  public function testDeletePromotion() {
    $promotion = $this->createEntity('commerce_promotion', [
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($promotion->toUrl('delete-form'));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    $this->container->get('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$promotion->id()]);
    $promotion_exists = (bool) Promotion::load($promotion->id());
    $this->assertEmpty($promotion_exists);
  }

}
