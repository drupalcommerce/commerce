<?php

namespace Drupal\Tests\commerce_number_pattern\FunctionalJavascript;

use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_number_pattern_test\Entity\EntityTestWithStore;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the number pattern admin UI.
 *
 * @group commerce
 */
class NumberPatternTest extends CommerceWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_number_pattern',
    'commerce_number_pattern_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_number_pattern',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests adding a number pattern.
   */
  public function testAdd() {
    $this->drupalGet('admin/commerce/config/number-patterns/add');
    $page = $this->getSession()->getPage();
    // Confirm that the target entity type field is hidden when there's only
    // one option.
    $this->assertSession()->fieldNotExists('targetEntityType');

    $page->fillField('label', 'Foo');
    $page->selectFieldOption('plugin', 'monthly');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Saved the Foo number pattern.');

    $number_pattern = NumberPattern::load('foo');
    $this->assertNotEmpty($number_pattern);
    $this->assertEquals('Foo', $number_pattern->label());
    $this->assertEquals('entity_test_with_store', $number_pattern->getTargetEntityTypeId());
    $this->assertEquals('monthly', $number_pattern->getPluginId());
  }

  /**
   * Tests editing a number pattern.
   */
  public function testEdit() {
    $number_pattern = NumberPattern::create([
      'id' => 'foo',
      'label' => 'Foo',
      'targetEntityType' => 'entity_test_with_store',
      'plugin' => 'yearly',
      'configuration' => [
        'initial_number' => 10,
        'padding' => 2,
      ],
    ]);
    $number_pattern->save();

    $this->drupalGet($number_pattern->toUrl('edit-form'));
    $this->assertNoField('configuration[yearly][per_store_sequence');
    $edit = [
      'label' => 'Foo!',
      'configuration[yearly][initial_number]' => 2,
      'configuration[yearly][padding]' => 5,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Foo! number pattern.');

    $number_pattern = NumberPattern::load('foo');
    $this->assertNotEmpty($number_pattern);
    $this->assertEquals($edit['label'], $number_pattern->label());
    $configuration = $number_pattern->getPluginConfiguration();
    $this->assertEquals($edit['configuration[yearly][initial_number]'], $configuration['initial_number']);
    $this->assertEquals($edit['configuration[yearly][padding]'], $configuration['padding']);
  }

  /**
   * Tests deleting a number pattern.
   */
  public function testDelete() {
    $number_pattern = NumberPattern::create([
      'id' => 'foo',
      'label' => 'Foo',
      'targetEntityType' => 'entity_test_with_store',
      'plugin' => 'yearly',
      'configuration' => [
        'initial_number' => 10,
        'padding' => 2,
      ],
    ]);
    $number_pattern->save();

    $this->drupalGet($number_pattern->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the number pattern @type?', ['@type' => $number_pattern->label()]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');
    $number_pattern_exists = (bool) NumberPattern::load($number_pattern->id());
    $this->assertEmpty($number_pattern_exists);
  }

  /**
   * Tests resetting a number pattern's sequence.
   */
  public function testResetSequence() {
    $number_pattern = NumberPattern::create([
      'id' => 'foo',
      'label' => 'Foo',
      'targetEntityType' => 'entity_test_with_store',
      'plugin' => 'infinite',
      'configuration' => [
        'initial_number' => 1,
        'padding' => 2,
      ],
    ]);
    $number_pattern->save();

    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    $number = $number_pattern->getPlugin()->generate($entity);
    $this->assertEquals(1, $number);

    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    $number = $number_pattern->getPlugin()->generate($entity);
    $this->assertEquals(2, $number);

    $this->drupalGet($number_pattern->toUrl('reset-sequence-form'));
    $this->assertSession()->pageTextContains(t('Are you sure you want to reset the sequence for the @type number pattern?', ['@type' => $number_pattern->label()]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Reset sequence');

    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    $number = $number_pattern->getPlugin()->generate($entity);
    $this->assertEquals(1, $number);
  }

}
