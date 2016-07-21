<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Create, view, edit, delete, and change promotion entities.
 *
 * @group commerce
 */
class PromotionTest extends CommerceBrowserTestBase {

  use StoreCreationTrait;
  use JavascriptTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'commerce_promotion'];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer promotions',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a promotion.
   */
  public function testCreatePromotion() {
    $this->createStore(NULL, NULL, 'default', TRUE);

    $this->drupalGet('admin/commerce/promotions');
    $this->getSession()->getPage()->clickLink('Add a new promotion');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('name[0][value]');

    $name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $name,
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains("The promotion $name has been successfully saved.");
    $promotion_count = $this->getSession()->getPage()->find('xpath', '//table/tbody/tr/td[text()="' . $name . '"]');
    $this->assertEquals(count($promotion_count), 1, 'promotions exists in the table.');
  }

  /**
   * Tests editing a promotion.
   */
  public function testEditPromotion() {
    $this->createStore(NULL, NULL, 'default', TRUE);

    $promotion = $this->createEntity('commerce_promotion', [
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($promotion->toUrl('edit-form'));
    $new_promotion_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_promotion_name,
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$promotion->id()]);
    $promotion_changed = Promotion::load($promotion->id());
    $this->assertEquals($new_promotion_name, $promotion_changed->getName(), 'The promotion name successfully updated.');
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
    $this->assertFalse($promotion_exists, 'The new promotion has been deleted from the database using UI.');
  }

}
