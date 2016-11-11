<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of numeric fields.
 *
 * @group field
 */
class CommerceNumberWidgetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'entity_test', 'field_ui', 'commerce');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(array(
      'view test entity',
      'administer entity_test content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
      'administer entity_test fields',
    )));
  }

  /**
   * Test decimal field.
   */
  public function testNumberDecimalField() {
    // Create a field with settings to validate.
    $field = $this->createNumberField('decimal', ['precision' => 8, 'scale' => 4]);
    $field_name = $field->getName();
    // Extract newly saved form display default mode number widget's
    // $placeholder, $min, $max, $default_value, $step, $prefix and $suffix
    // settings and test if they are set to field properly. As a helper random
    // $value for a "user" to insert and $field_name for futher using are
    // appended to the settings array and extracted too.
    $settings = $this->saveNumberFormDisplaySettings($field, $field_name, $get_random_settings = []);
    extract($settings);

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number_decimal',
      ))
      ->save();

    // $id = the id of the created entity.
    $id = $this->displaySubmitAssertForm($settings);

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = array(
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save decimal value with more than one decimal point.');
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = array(
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save decimal value with minus sign in the wrong position.');
    }

    // Edit the field settings with new explicit values.
    $settings = array(
      'placeholder' => 'PlaceholderDecimal',
      'min' => '-8.8888',
      'max' => '13.3332',
      'default_value' => '0',
      'value' => '2.2222',
      'step' => '2.2222',
      'prefix' => 'PrefixDecimal',
      'suffix' => 'SuffixDecimal',
    );

    // As the field's base settings min may not be decreased and max
    // increased on a form display mode, so let's set those on the field
    // in order to not depend on dynamically generated random min and max.
    $field->setSettings(['min' => '-8.8888', 'max' => '13.3332']);
    $field->save();

    $saved_settings = $this->saveNumberFormDisplaySettings($field, $field_name, $settings);
    extract($saved_settings);
    // Check if the entity works with new settings.
    $id = $this->displaySubmitAssertForm($saved_settings);

    // Try to create entries with wrong steps.
    $wrong_steps = array(
      '2.2224',
      '1.1111',
      '1',
      '0.5',
    );

    foreach ($wrong_steps as $wrong_step) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => (string) ($value + $wrong_step),
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Correctly failed to save decimal value ' . $edit["{$field_name}[0][value]"] . ' added with the wrong step ' . $wrong_step . ' instead of ' . $step . '.');
    }
  }

  /**
   * Test integer field.
   */
  public function testNumberIntegerField() {
    // Create a field to validate.
    $field = $this->createNumberField('integer');
    $field_name = $field->getName();
    $storage = $field->getFieldStorageDefinition();
    $base_settings = $field->getItemDefinition()->getSettings();
    $settings = $this->saveNumberFormDisplaySettings($field, $field_name, $get_random_settings = []);
    extract($settings);

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number_integer',
        'settings' => array(
          'prefix_suffix' => FALSE,
        ),
      ))
      ->save();
    // Check the storage schema.
    $expected = array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'unsigned' => '',
          'size' => 'normal',
        ),
      ),
      'unique keys' => array(),
      'indexes' => array(),
      'foreign keys' => array(),
    );
    $this->assertEqual($storage->getSchema(), $expected);
    $id = $this->displaySubmitAssertForm($settings);

    // Try to set a value below the minimum value.
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => $min - 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name must be higher than or equal to %minimum.', array('%name' => $field_name, '%minimum' => $min)), 'Correctly failed to save integer value less than minimum allowed value.');

    // Try to set a decimal value.
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => $min + 0.5,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Correctly failed to save decimal value to integer field.');

    // Try to set a value above the maximum value.
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => $max + 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name must be lower than or equal to %maximum.', array('%name' => $field_name, '%maximum' => $max)), 'Correctly failed to save integer value greater than maximum allowed value.');

    // Try to set a wrong integer value.
    $this->drupalGet('entity_test/add');
    $edit = array(
      "{$field_name}[0][value]" => '20-40',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save wrong integer value.');

    // Test for the content attribute when prefix and suffix are set to display
    // or not on a field display formatter (sic!) setting. Note that only prefix
    // and suffix set on field base settings are displayed here.
    // For the test use valid values in the min-max range.
    // @todo: consider moving this to testNumberFormatter().
    $i = 0;
    $not = 'not';
    $method = 'assertNoFieldByXpath';
    $value = $min;

    while (($value += $step) && $value < $max) {
      if ($value == $default_value) {
        continue;
      }
      elseif ($i == 3) {
        $not = '';
        $method = 'assertFieldByXpath';
        entity_get_display('entity_test', 'entity_test', 'default')
          ->setComponent($field_name, array(
            'type' => 'number_integer',
            'settings' => array(
              'prefix_suffix' => TRUE,
            ),
          ))
          ->save();
      }
      elseif ($i > 5) {
        break;
      }
      $i++;

      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => (string) $value,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
      $id = isset($match[1]) ? $match[1] : '';
      $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
      $this->drupalGet('entity_test/' . $id);
      $this->{$method}('//div[@content="' . $value . '"]', $base_settings['prefix'] . $value . $base_settings['suffix'], 'The "content" attribute has ' . $not . ' been set to the value of the field, and the ' . $base_settings['prefix'] . ' and ' . $base_settings['suffix'] . ' set on field base settings are ' . $not . ' being displayed.');
    }

    // Edit the field settings with the new explicit values.
    $settings = array(
      'placeholder' => 'PlaceholderInteger',
      'min' => '-8888',
      'max' => '13332',
      'default_value' => '0',
      'value' => '2222',
      'step' => '2222',
      'prefix' => 'PrefixInteger',
      'suffix' => 'SuffixInteger',
    );

    $field->setSettings(['min' => '-8888', 'max' => '13332']);
    $field->save();

    $saved_settings = $this->saveNumberFormDisplaySettings($field, $field_name, $settings);
    extract($saved_settings);
    $id = $this->displaySubmitAssertForm($saved_settings);

    // Try to create entries with wrong steps.
    $wrong_steps = array(
      '2224',
      '1111',
      '11',
      '0.5',
    );

    foreach ($wrong_steps as $wrong_step) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => (string) ($value + $wrong_step),
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Correctly failed to save integer value ' . $edit["{$field_name}[0][value]"] . ' added with the wrong step ' . $wrong_step . ' instead of ' . $step . '.');
    }
  }

  /**
   * Test float field.
   */
  public function testNumberFloatField() {
    // Create a field to validate.
    $field = $this->createNumberField('float');
    $field_name = $field->getName();
    $settings = $this->saveNumberFormDisplaySettings($field, $field_name, $get_random_settings = []);
    extract($settings);

    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'number_decimal',
      ))
      ->save();

    $id = $this->displaySubmitAssertForm($settings);

    // Ensure that the 'number_decimal' formatter displays the number in a
    // default format.
    // @todo: consider to remove this as it is actually duplicate of
    // the same testNumberFormatter() assert. Above all it sometimes failes on
    // test entities, although on nodes in testNumberFormatter() works as expected.
    // Also, formatting/rounding algorithm is not persistant and looks like as random.
    $this->drupalGet('entity_test/' . $id);
    $this->assertRaw(round($value, 2));

    // Try to create entries with more than one decimal separator; assert fail.
    $wrong_entries = array(
      '3.14.159',
      '0..45469',
      '..4589',
      '6.459.52',
      '6.3..25',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save float value with more than one decimal point.');
    }

    // Try to create entries with minus sign not in the first position.
    $wrong_entries = array(
      '3-3',
      '4-',
      '1.3-',
      '1.2-4',
      '-10-10',
    );

    foreach ($wrong_entries as $wrong_entry) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => $wrong_entry,
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name must be a number.', array('%name' => $field_name)), 'Correctly failed to save float value with minus sign in the wrong position.');
    }

    // Edit the field settings with the new explicit values.
    $settings = array(
      'placeholder' => 'PlaceholderFloat',
      'min' => '-49.38268',
      'max' => '74.07402',
      'default_value' => '0',
      'value' => '12.34567',
      'step' => '12.34567',
      'prefix' => 'PrefixFloat',
      'suffix' => 'SuffixFloat',
    );

    $field->setSettings(['min' => '-49.38268', 'max' => '74.07402']);
    $field->save();

    $saved_settings = $this->saveNumberFormDisplaySettings($field, $field_name, $settings);
    extract($saved_settings);
    $id = $this->displaySubmitAssertForm($saved_settings);

    // Try to create entries with wrong steps.
    $wrong_steps = array(
      '22.22234',
      '11.11111',
      '1',
      '0.00012',
    );

    foreach ($wrong_steps as $wrong_step) {
      $this->drupalGet('entity_test/add');
      $edit = array(
        "{$field_name}[0][value]" => (string) ($value + $wrong_step),
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $this->assertRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Correctly failed to save float value ' . $edit["{$field_name}[0][value]"] . ' added with the wrong step ' . $wrong_step . ' instead of ' . $step . '.');
    }
  }

  /**
   * Test default formatter behavior.
   */
  public function testNumberFormatter() {
    $type = Unicode::strtolower($this->randomMachineName());
    $float_field = Unicode::strtolower($this->randomMachineName());
    $integer_field = Unicode::strtolower($this->randomMachineName());
    $thousand_separators = array('', '.', ',', ' ', chr(8201), "'");
    $decimal_separators = array('.', ',');
    $prefix = $this->randomMachineName();
    $suffix = $this->randomMachineName();
    $random_float = rand(0, pow(10, 6));
    $random_integer = rand(0, pow(10, 6));

    // Create a content type containing float and integer fields.
    $this->drupalCreateContentType(array('type' => $type));

    FieldStorageConfig::create(array(
      'field_name' => $float_field,
      'entity_type' => 'node',
      'type' => 'float',
    ))->save();

    FieldStorageConfig::create(array(
      'field_name' => $integer_field,
      'entity_type' => 'node',
      'type' => 'integer',
    ))->save();

    FieldConfig::create([
      'field_name' => $float_field,
      'entity_type' => 'node',
      'bundle' => $type,
      'settings' => array(
        'prefix' => $prefix,
        'suffix' => $suffix,
      ),
    ])->save();

    FieldConfig::create([
      'field_name' => $integer_field,
      'entity_type' => 'node',
      'bundle' => $type,
      'settings' => array(
        'prefix' => $prefix,
        'suffix' => $suffix,
      ),
    ])->save();

    entity_get_form_display('node', $type, 'default')
      ->setComponent($float_field, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00',
        ),
      ))
      ->setComponent($integer_field, array(
        'type' => 'number',
        'settings' => array(
          'placeholder' => '0.00',
        ),
      ))
      ->save();

    entity_get_display('node', $type, 'default')
      ->setComponent($float_field, array(
        'type' => 'number_decimal',
      ))
      ->setComponent($integer_field, array(
        'type' => 'number_unformatted',
      ))
      ->save();

    // Create a node to test formatters.
    $node = Node::create([
      'type' => $type,
      'title' => $this->randomMachineName(),
      $float_field => ['value' => $random_float],
      $integer_field => ['value' => $random_integer],
    ]);
    $node->save();

    // Go to manage display page.
    $this->drupalGet("admin/structure/types/manage/$type/display");

    // Configure number_decimal formatter for the 'float' field type.
    $thousand_separator = $thousand_separators[array_rand($thousand_separators)];
    $decimal_separator = $decimal_separators[array_rand($decimal_separators)];
    $scale = rand(0, 10);

    $this->drupalPostAjaxForm(NULL, array(), "${float_field}_settings_edit");
    $edit = array(
      "fields[${float_field}][settings_edit_form][settings][prefix_suffix]" => TRUE,
      "fields[${float_field}][settings_edit_form][settings][scale]" => $scale,
      "fields[${float_field}][settings_edit_form][settings][decimal_separator]" => $decimal_separator,
      "fields[${float_field}][settings_edit_form][settings][thousand_separator]" => $thousand_separator,
    );
    $this->drupalPostAjaxForm(NULL, $edit, "${float_field}_plugin_settings_update");
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Check number_decimal and number_unformatted formatters behavior.
    $this->drupalGet('node/' . $node->id());
    $float_formatted = number_format($random_float, $scale, $decimal_separator, $thousand_separator);
    $this->assertRaw("$prefix$float_formatted$suffix", 'Prefix and suffix added');
    $this->assertRaw((string) $random_integer);

    // Configure the number_decimal formatter.
    entity_get_display('node', $type, 'default')
      ->setComponent($integer_field, array(
        'type' => 'number_integer',
      ))
      ->save();
    $this->drupalGet("admin/structure/types/manage/$type/display");

    $thousand_separator = $thousand_separators[array_rand($thousand_separators)];

    $this->drupalPostAjaxForm(NULL, array(), "${integer_field}_settings_edit");
    $edit = array(
      "fields[${integer_field}][settings_edit_form][settings][prefix_suffix]" => FALSE,
      "fields[${integer_field}][settings_edit_form][settings][thousand_separator]" => $thousand_separator,
    );
    $this->drupalPostAjaxForm(NULL, $edit, "${integer_field}_plugin_settings_update");
    $this->drupalPostForm(NULL, array(), t('Save'));

    // Check number_integer formatter behavior.
    $this->drupalGet('node/' . $node->id());

    $integer_formatted = number_format($random_integer, 0, '', $thousand_separator);
    $this->assertRaw($integer_formatted, 'Random integer formatted');
  }

  /**
   * Tests setting the minimum value of a float field through the interface.
   */
  public function testCreateNumberFloatField() {
    // Create a float field.
    $field = $this->createNumberField('float', [], ['min' => '0']);

    // Set the minimum value to a float value.
    $this->assertSetMinimumValue($field, 0.0001);
    // Set the minimum value to an integer value.
    $this->assertSetMinimumValue($field, 1);
  }

  /**
   * Tests setting the minimum value of a decimal field through the interface.
   */
  public function testCreateNumberDecimalField() {
    // Create a decimal field.
    $field = $this->createNumberField('decimal', [], ['min' => '0']);

    // Set the minimum value to a decimal value.
    $this->assertSetMinimumValue($field, 0.1);
    // Set the minimum value to an integer value.
    $this->assertSetMinimumValue($field, 1);
  }

  /**
   * Helper function to set the minimum value of a field.
   */
  private function assertSetMinimumValue($field, $minimum_value) {
    $field_configuration_url = 'entity_test/structure/entity_test/fields/entity_test.entity_test.' . $field->getName();

    // Set the minimum value.
    $edit = array(
      'settings[min]' => $minimum_value,
    );
    $this->drupalPostForm($field_configuration_url, $edit, t('Save settings'));
    // Check if an error message is shown.
    $this->assertNoRaw(t('%name is not a valid number.', array('%name' => t('Minimum'))), 'Saved ' . gettype($minimum_value) . '  value as minimal value on a ' . $field->getType() . ' field');
    // Check if a success message is shown.
    $this->assertRaw(t('Saved %label configuration.', array('%label' => $field->getLabel())));
    // Check if the minimum value was actually set.
    $this->drupalGet($field_configuration_url);
    $this->assertFieldById('edit-settings-min', $minimum_value, 'Minimal ' . gettype($minimum_value) . '  value was set on a ' . $field->getType() . ' field.');
  }

  /**
   * Creates a number field of the given type with optional settings.
   */
  private function createNumberField($type, $config_settings = [], $field_settings = []) {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $config = array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $type,
    );
    if (!empty($config_settings)) {
      $config['settings'] = $config_settings;
    }
    $storage = FieldStorageConfig::create($config);
    $storage->save();

    $field = [
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ];
    if (!empty($field_settings)) {
      $field['settings'] = $field_settings;
    }
    else {
      // Set up default random settings for a field.
      $field['settings'] = $this->getRandomNumberSettings($storage);
    }
    $field = FieldConfig::create($field);
    $field->save();

    return $field;
  }

  /**
   * Helper function to set up a number field random values.
   */
  private function getRandomNumberSettings($field) {
    // The highest number to choose random values from.
    $ceil = 9999;
    $off = COMMERCE_NUMBER_FIELD_TEST_DECIMALS_OFFSET;
    $ceil = $ceil < $off * 2 ? $off * 2 : $ceil;
    $value = $step = '';
    $type = $field->getType();
    $settings = $field->getSettings();
    extract($settings);
    $init_min = empty($unsigned) ? rand(-$ceil, 0) : rand(0, $ceil - $off);
    $no_min = !isset($min) || !is_numeric($min);
    $no_max = !isset($max) || !is_numeric($max);

    // We need to substract 3 from min-max range in order to have a place for a
    // value and default_value (+.decimals). For example, if happens $min = 0
    // then $max = 3-9999 AND $default_value = 1-9998 AND $value = 2-9998.
    $min = $no_min ? $init_min : rand($min, $no_max ? $ceil - $off : $max);
    $max = $no_max ? rand($min + $off, $ceil) : rand($min + $off, $max);
    $value = rand($min + 1, $max - 1);
    $default_value = rand($min + 1, $max - 1);
    if (isset($settings['min'])) {
      $min = $min < $settings['min'] ? $settings['min'] : $min;
    }
    if (isset($settings['max'])) {
      $max = $max > $settings['max'] ? $settings['max'] : $max;
    }

    if ($type == 'integer') {
      $valid = $min;
      $tmp_max = $max;
      $tmp_value = $value;
      $tmp_default_value = $default_value;
      $half = abs($max - $min) / 2;
      $half = $half > 2 ? $half : 1;
      $step = $half == 1 ? $half : $half + 1;

      while ($step > $half) {
        $step = explode('0.', $this->getRandomStep($scale = rand(1, strlen($max))))[1] + 0;
      }
      while ($tmp_max >= $valid) {
        $default_value = $tmp_default_value > $valid ? $valid : $default_value;
        $value = $tmp_value > $valid ? $valid : $value;
        $max = $valid;
        $valid += $step;
      }
      while ($default_value == $value && $default_value >= $min) {
        $default_value = $valid = $valid - $step;
      }
    }
    elseif ($type == 'decimal' || $type == 'float') {
      // Float type has no $scale, so we use any scale in a range 1-9.
      $scale = empty($scale) ? rand(1, 9) : $scale;
      extract($this->getRandomFloatValues($scale, $min, $max, $value, $default_value));
    }

    return array(
      'placeholder' => 'RandomPlaceholder-' . $this->randomMachineName(),
      'min' => (string) $min,
      'max' => (string) $max,
      'default_value' => (string) $default_value,
      'value' => (string) $value,
      'step' => (string) $step,
      'prefix' => 'RandomPrefix-' . $this->randomMachineName(),
      'suffix' => 'RandomSuffix-' . $this->randomMachineName(),
    );
  }

  /**
   * Helper function to get random float step, min, max, value, default_value.
   */
  private function getRandomFloatValues($scale, $min, $max, $value, $default_value) {
    $values = [];
    // Use PHP BCMath functions in order to get valid float numbers.
    $half = bcdiv(abs(bcsub($max, $min, $scale)), 2, $scale) + 0;
    $values['min'] = $min = $valid = bcadd($min, $this->getRandomStep($scale), $scale) + 0;
    $values['default_value'] = $default_value = $default_value < $min ? $min : $default_value;
    $values['value'] = $value = $value < $min ? $min : $value;
    $values['step'] = $step = ($this->getRandomStep($scale) + 0) + rand(0, strlen($max));
    $pow = pow(0.1, $scale);

    while (bccomp($step, $half, $scale) >= 0 && bccomp($step, $pow, $scale) == 1) {
      $values['step'] = $step = bcsub($step, $pow, $scale);
    }
    while (bccomp($max, $valid, $scale) >= 0) {
      $values['default_value'] = bccomp($default_value, $valid, $scale) == 1 ? $valid : $values['default_value'];
      $values['value'] = bccomp($value, $valid, $scale) == 1 ? $valid : $values['value'];
      $values['max'] = $valid;
      $valid = bcadd($valid, $step, $scale) + 0;
    }
    while (bccomp($values['default_value'], $values['value'], $scale) == 0 && bccomp($values['default_value'], $min, $scale) >= 0) {
      $values['default_value'] = $valid = bcsub($valid, $step, $scale) + 0;
    }

    return $values;
  }

  /**
   * Helper function to get a random step.
   */
  private function getRandomStep($scale) {
    $pow = pow(0.1, $scale);
    $step = $pow * substr(mt_rand(), 0, $scale);
    return empty($step) ? $pow : $step;
  }

  /**
   * Helper function to save a number field attributes for the form display.
   */
  private function saveNumberFormDisplaySettings($field, $field_name, $test_settings = []) {
    $settings = empty($test_settings) ? $this->getRandomNumberSettings($field) : $test_settings;

    $widget = entity_get_form_display('entity_test', 'entity_test', 'default');
    $widget->setComponent($field_name, array(
      'type' => 'commerce_number',
      'settings' => $settings,
    ))
    ->save();

    $widget_settings = $widget->getRenderer($field_name)->getFormDisplayModeSettings();
    // As only default_value is saved on the widget we need to restore a value
    // prepared by getRandomNumberSettings() for a "user" to insert in the field.
    $widget_settings['value'] = $settings['value'];
    $widget_settings['field_name'] = $field_name;

    return $widget_settings;
  }

  /**
   * Helper function to display and submit a number field.
   */
  private function displaySubmitAssertForm($settings) {
    extract($settings);
    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][value]", '', 'Widget is displayed');
    $this->assertNumberFieldAttributes($settings);

    // Add the $value and submit the field.
    $edit = array(
      "{$field_name}[0][value]" => $value,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    // Check $match[] for existance and let the test just fail. No exceptions thrown.
    $id = isset($match[1]) ? $match[1] : '';
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)), 'Entity was created');
    $this->assertNumberAfterFormSubmit($settings);

    return $id;
  }

  /**
   * Helper function to assert a number field attributes.
   */
  private function assertNumberFieldAttributes($settings) {
    extract($settings);
    $this->assertRaw('placeholder="' . $placeholder . '"');
    $this->assertRaw('min="' . $min . '"');
    $this->assertRaw('max="' . $max . '"');
    $this->assertRaw('value="' . $default_value . '"', 'Raw default value "' . $default_value . '" found');
    $this->assertRaw('step="' . $step . '"');
    $this->assertRaw($prefix);
    $this->assertRaw($suffix);
  }

  /**
   * Helper function to assert a number field after form submit.
   */
  private function assertNumberAfterFormSubmit($settings) {
    extract($settings);
    // Check common errors and as a consiquence value="$value" is left in place.
    $this->assertNoRaw(t('%name must be higher than or equal to %minimum.', array('%name' => $field_name, '%minimum' => $min)), 'Submitted value ' . $value . ' higher than or equal to minimum ' . $min);
    $this->assertNoRaw(t('%name must be lower than or equal to %maximum.', array('%name' => $field_name, '%maximum' => $max)), 'Submitted value ' . $value . ' lower than or equal to maximum ' . $max);
    $this->assertNoRaw(t('%name must be a number.', array('%name' => $field_name)), 'Submitted value ' . $value . ' is numeric.');
    $this->assertNoRaw(t('%name is not a valid number.', array('%name' => $field_name)), 'Submitted value ' . $value . ' is valid number.');
    // The attribute value="$value" need to be checked instead of string $value
    // as there are may be similar strings on a page.
    $this->assertNoRaw('value="' . $value . '"', 'Submitted value ' . $value . ' cleared out after form submit.');
  }

}

/**
 * The offset for .decimals in a min-max range to keep values not adjacent
 * to each other.
 */
if (!defined('COMMERCE_NUMBER_FIELD_TEST_DECIMALS_OFFSET')) {
  define('COMMERCE_NUMBER_FIELD_TEST_DECIMALS_OFFSET', 3);
}
