<?php

namespace Drupal\Tests\commerce_tax\Kernel;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the 'commerce_tax_number' field type.
 *
 * @group commerce
 */
class TaxNumberItemTest extends CommerceKernelTestBase {

  /**
   * A test field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_tax',
    'commerce_tax_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_tax_number',
      'entity_type' => 'entity_test',
      'type' => 'commerce_tax_number',
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_name' => 'test_tax_number',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'VAT Number',
      'settings' => [
        'countries' => [],
        'verify' => TRUE,
        'allow_unverified' => TRUE,
      ],
    ]);
    $this->field->save();
  }

  /**
   * Tests the field.
   */
  public function testField() {
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'serbian_vat',
        'value' => '123',
        'verification_state' => VerificationResult::STATE_SUCCESS,
        'verification_timestamp' => strtotime('2019/08/08'),
        'verification_result' => ['name' => 'Bryan Centarro'],
      ],
    ]);
    $entity->save();
    $entity = $this->reloadEntity($entity);

    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $this->assertEquals('serbian_vat', $tax_number_item->type);
    $this->assertEquals('123', $tax_number_item->value);
    $this->assertEquals(VerificationResult::STATE_SUCCESS, $tax_number_item->verification_state);
    $this->assertEquals(strtotime('2019/08/08'), $tax_number_item->verification_timestamp);
    $this->assertEquals(['name' => 'Bryan Centarro'], $tax_number_item->verification_result);
    $type_plugin = $tax_number_item->getTypePlugin();
    $this->assertNotEmpty($type_plugin);
    $this->assertEquals('serbian_vat', $type_plugin->getPluginId());

    // Confirm that changing the type resets the verification state.
    $tax_number_item->type = 'invalid';
    $this->assertNull($tax_number_item->verification_state);
    $this->assertNull($tax_number_item->verification_timestamp);
    $this->assertNull($tax_number_item->verification_result);
    // Test type fallback.
    $type_plugin = $tax_number_item->getTypePlugin();
    $this->assertNotEmpty($type_plugin);
    $this->assertEquals('other', $type_plugin->getPluginId());
  }

  /**
   * Tests checking whether value can be used for tax calculation.
   */
  public function testCheckValue() {
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'serbian_vat',
        'value' => '123456',
        'verification_state' => VerificationResult::STATE_UNKNOWN,
      ],
    ]);
    $entity->save();
    $entity = $this->reloadEntity($entity);

    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $this->assertTrue($tax_number_item->checkValue('serbian_vat'));
    // Type mismatch.
    $this->assertFalse($tax_number_item->checkValue('european_union_vat'));
    // Empty value.
    $tax_number_item->value = '';
    $this->assertFalse($tax_number_item->checkValue('other'));
    // No verification_state specified.
    $tax_number_item->value = '123456';
    $tax_number_item->verification_state = NULL;
    $this->assertFalse($tax_number_item->checkValue('serbian_vat'));
    // Verification required.
    $this->field->setSetting('allow_unverified', FALSE);
    $this->field->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $this->assertFalse($tax_number_item->checkValue('serbian_vat'));
    $tax_number_item->verification_state = VerificationResult::STATE_SUCCESS;
    $this->assertTrue($tax_number_item->checkValue('serbian_vat'));

    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'other',
        'value' => '123',
      ],
    ]);
    $entity->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    // Confirm that the verification_state is not checked
    // if the type doesn't support verification.
    $this->assertTrue($tax_number_item->checkValue('other'));
  }

  /**
   * Tests the allowed countries setting.
   */
  public function testCountries() {
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'other',
        'value' => '123',
      ],
    ]);
    $entity->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();

    // Unrestricted country list.
    $country_repository = $this->container->get('address.country_repository');
    $country_list = $country_repository->getList();
    $this->assertEquals(array_keys($country_list), $tax_number_item->getAllowedCountries());
    $this->assertEquals(['european_union_vat', 'other', 'serbian_vat'], $tax_number_item->getAllowedTypes());

    // Restricted to the EU.
    $this->field->setSetting('countries', ['EU']);
    $this->field->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    // Confirm that "EU" expands to the full list of EU countries.
    $this->assertNotContains('EU', $tax_number_item->getAllowedCountries());
    $this->assertCount(30, $tax_number_item->getAllowedCountries());
    $this->assertEquals(['european_union_vat'], $tax_number_item->getAllowedTypes());

    // Restricted to EU + a non-EU country.
    $this->field->setSetting('countries', ['EU', 'US']);
    $this->field->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    // Confirm that "EU" expands to the full list of EU countries.
    $this->assertNotContains('EU', $tax_number_item->getAllowedCountries());
    $this->assertContains('US', $tax_number_item->getAllowedCountries());
    $this->assertCount(31, $tax_number_item->getAllowedCountries());
    $this->assertEquals(['european_union_vat', 'other'], $tax_number_item->getAllowedTypes());

    // Restricted to a non-EU country.
    $this->field->setSetting('countries', ['US']);
    $this->field->save();
    $entity = $this->reloadEntity($entity);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $this->assertEquals(['US'], $tax_number_item->getAllowedCountries());
    $this->assertEquals(['other'], $tax_number_item->getAllowedTypes());
  }

  /**
   * Tests the validation.
   */
  public function testValidation() {
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'other',
        'value' => 'MK1234567',
      ],
    ]);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    // Fallback plugin, value always accepted (no validation/verification).
    $violations = $tax_number_item->validate();
    $this->assertCount(0, $violations);

    // Missing type.
    $tax_number_item->setValue([
      'value' => 'MK1234567',
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('type', $violations[0]->getPropertyPath());
    $this->assertEquals('This value should not be null.', $violations[0]->getMessage());

    // Unrecognized type.
    $tax_number_item->setValue([
      'type' => 'INVALID',
      'value' => 'MK1234567',
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('type', $violations[0]->getPropertyPath());
    $this->assertEquals('Invalid type specified.', $violations[0]->getMessage());

    // Unrecognized verification_state.
    $tax_number_item->setValue([
      'type' => 'other',
      'value' => 'MK1234567',
      'verification_state' => 'INVALID',
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('verification_state', $violations[0]->getPropertyPath());
    $this->assertEquals('Invalid verification_state specified.', $violations[0]->getMessage());

    // Value too long.
    $entity->set('test_tax_number', [
      'type' => 'serbian_vat',
      'value' => hash('sha512', 'TEST'),
    ]);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $expected_message = new FormattableMarkup('%name: may not be longer than 64 characters.', [
      '%name' => $this->field->label(),
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('value', $violations[0]->getPropertyPath());
    $this->assertEquals($expected_message->__toString(), $violations[0]->getMessage());

    // Invalid format.
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '1234',
    ]);
    $expected_message = new FormattableMarkup('%name is not in the right format. Example: 901.', [
      '%name' => $this->field->label(),
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('value', $violations[0]->getPropertyPath());
    $this->assertEquals($expected_message->__toString(), $violations[0]->getMessage());

    // Invalid format (verification failed).
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '402',
    ]);
    $expected_message = new FormattableMarkup('%name could not be verified.', [
      '%name' => $this->field->label(),
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('value', $violations[0]->getPropertyPath());
    $this->assertEquals($expected_message->__toString(), $violations[0]->getMessage());

    // Valid format (verification succeeded).
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '403',
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(0, $violations);

    // Valid format (verification service unavailable).
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '190',
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(0, $violations);

    // Verification required, verification service unavailable.
    $this->field->setSetting('allow_unverified', FALSE);
    $this->field->save();
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'serbian_vat',
        'value' => '190',
      ],
    ]);
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $expected_message = new FormattableMarkup('%name could not be verified.', [
      '%name' => $this->field->label(),
    ]);
    $violations = $tax_number_item->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('value', $violations[0]->getPropertyPath());
    $this->assertEquals($expected_message->__toString(), $violations[0]->getMessage());
  }

  /**
   * Tests the preSave() logic.
   */
  public function testPreSave() {
    // Verification failed.
    $entity = EntityTest::create([
      'test_tax_number' => [
        'type' => 'serbian_vat',
        'value' => '402',
      ],
    ]);
    $entity->save();
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();

    $this->assertEquals(VerificationResult::STATE_FAILURE, $tax_number_item->verification_state);
    $this->assertEquals(\Drupal::time()->getRequestTime(), $tax_number_item->verification_timestamp);
    $this->assertEmpty($tax_number_item->verification_result);

    // Verification service unavailable.
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '190',
    ]);
    $entity->save();
    $this->assertEquals(VerificationResult::STATE_UNKNOWN, $tax_number_item->verification_state);
    $this->assertEquals(\Drupal::time()->getRequestTime(), $tax_number_item->verification_timestamp);
    $this->assertEquals(['error' => 'http_429'], $tax_number_item->verification_result);

    // Verification succeeded.
    $tax_number_item->setValue([
      'type' => 'serbian_vat',
      'value' => '403',
    ]);
    $entity->save();

    $this->assertEquals(VerificationResult::STATE_SUCCESS, $tax_number_item->verification_state);
    $this->assertEquals(\Drupal::time()->getRequestTime(), $tax_number_item->verification_timestamp);
    $verification_result = $tax_number_item->verification_result;
    $this->assertArrayHasKey('name', $verification_result);
    $this->assertEquals('John Smith', $verification_result['name']);
    $original_nonce = $verification_result['nonce'];

    // Confirm that verification only runs once.
    $this->container->get('entity.memory_cache')->deleteAll();
    $entity->save();
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $tax_number_item */
    $tax_number_item = $entity->get('test_tax_number')->first();
    $this->assertEquals($original_nonce, $tax_number_item->verification_result['nonce']);
  }

}
