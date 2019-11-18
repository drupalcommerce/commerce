<?php

namespace Drupal\Tests\commerce_store\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the commerce_store_datetime formatter and widget.
 *
 * @group commerce
 */
class StoreDateTimeTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'commerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer entity_test content',
      'view test entity',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_date',
      'entity_type' => 'entity_test',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'datetime'],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'test_date',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'Test date!',
    ]);
    $field->save();

    $form_display = commerce_get_entity_display('entity_test', 'entity_test', 'form');
    $form_display->setComponent('test_date', [
      'type' => 'commerce_store_datetime',
    ])->save();

    $view_display = commerce_get_entity_display('entity_test', 'entity_test', 'view');
    $view_display->setComponent('test_date', [
      'type' => 'commerce_store_datetime',
    ])->save();
  }

  /**
   * Tests the widget.
   */
  public function testWidget() {
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
    ]);

    $date = new DrupalDateTime('2019-10-31 12:15:30', 'UTC');
    // Confirm that a date/time value can be added.
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Test date!');
    $this->assertSession()->fieldExists('test_date[0][value][date]');
    $this->assertSession()->fieldExists('test_date[0][value][time]');
    $this->submitForm([
      'test_date[0][value][date]' => $date->format('Y-m-d'),
      'test_date[0][value][time]' => $date->format('H:i:s'),
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $entity = $this->reloadEntity($entity);
    $this->assertEquals($date->format($storage_format), $entity->get('test_date')->value);

    // Confirm that a date/time value can be edited.
    $new_date = new DrupalDateTime('2019-11-15 11:10:15', 'UTC');
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Test date!');
    $this->assertSession()->fieldValueEquals('test_date[0][value][date]', $date->format('Y-m-d'));
    $this->assertSession()->fieldValueEquals('test_date[0][value][time]', $date->format('H:i:s'));
    $this->submitForm([
      'test_date[0][value][date]' => $new_date->format('Y-m-d'),
      'test_date[0][value][time]' => $new_date->format('H:i:s'),
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $entity = $this->reloadEntity($entity);
    $this->assertEquals($new_date->format($storage_format), $entity->get('test_date')->value);

    // Confirm that changing the store timezone does not change the value.
    $this->store->setTimezone('America/Chicago');
    $this->store->save();

    $new_date = new DrupalDateTime('2019-11-15 11:10:15', 'UTC');
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('test_date[0][value][date]', $new_date->format('Y-m-d'));
    $this->assertSession()->fieldValueEquals('test_date[0][value][time]', $new_date->format('H:i:s'));

    // Confirm that it is possible to enter just a date.
    $storage_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $field_storage = FieldStorageConfig::load('entity_test.test_date');
    $field_storage->setSetting('datetime_type', 'date');
    $field_storage->save();

    $date = new DrupalDateTime('2019-10-31', 'UTC');
    $new_date = new DrupalDateTime('2019-11-15', 'UTC');
    $entity = $this->reloadEntity($entity);
    $entity->set('test_date', $date->format($storage_format));
    $entity->save();
    $this->drupalGet($entity->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Test date!');
    $this->assertSession()->fieldValueEquals('test_date[0][value][date]', $date->format('Y-m-d'));
    $this->assertSession()->fieldNotExists('test_date[0][value][time]');
    $this->submitForm([
      'test_date[0][value][date]' => $new_date->format('Y-m-d'),
    ], 'Save');
    $this->assertSession()->pageTextContains('entity_test 1 has been updated.');

    $entity = $this->reloadEntity($entity);
    $this->assertEquals($new_date->format($storage_format), $entity->get('test_date')->value);
  }

  /**
   * Tests the formatter.
   */
  public function testFormatter() {
    $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    $date = new DrupalDateTime('2019-10-31 12:15:30', 'UTC');
    $entity = $this->createEntity('entity_test', [
      'name' => 'Test',
      'test_date' => $date->format($storage_format),
    ]);

    // Confirm that the value is rendered using the "medium" date format.
    $date_format = DateFormat::load('medium');
    $date_pattern = $date_format->getPattern();
    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertSession()->pageTextContains('Test date!');
    $this->assertSession()->pageTextContains($date->format($date_pattern));

    // Confirm that a custom date format can be used.
    $date_format = DateFormat::create([
      'id' => 'test_format',
      'name' => 'Test Format',
      'pattern' => 'Y-m-d H:i:s T',
    ]);
    $date_format->save();

    $view_display = commerce_get_entity_display('entity_test', 'entity_test', 'view');
    $view_display->setComponent('test_date', [
      'type' => 'commerce_store_datetime',
      'settings' => [
        'date_format' => 'test_format',
      ],
    ])->save();

    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertSession()->pageTextContains('2019-10-31 12:15:30 AEDT');

    // Confirm that changing the timezone does not change the value.
    $this->store->setTimezone('America/Chicago');
    $this->store->save();

    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertSession()->pageTextContains('2019-10-31 12:15:30 CDT');

    // Confirm that the site timezone is used if there is no store.
    $this->store->delete();
    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertSession()->pageTextContains('2019-10-31 12:15:30 AEDT');

    // Confirm that the "fallback" date format is used if the specified one
    // is missing.
    $date_format->delete();
    $this->drupalGet($entity->toUrl('canonical'));
    $this->assertSession()->pageTextContains('10/31/2019 - 12:15');
  }

}
