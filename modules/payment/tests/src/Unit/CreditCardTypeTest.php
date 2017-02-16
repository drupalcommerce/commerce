<?php

namespace Drupal\Tests\commerce_payment\Unit;

use Drupal\commerce_payment\CreditCardType;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_payment\CreditCardType
 * @group commerce
 */
class CreditCardTypeTest extends UnitTestCase {

  /**
   * The credit card type definition array.
   *
   * @var array
   */
  protected $definition;

  /**
   * The credit card type.
   *
   * @var \Drupal\commerce_payment\CreditCardType
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->definition = [
      'id' => 'dummy',
      'label' => 'DummyCard',
      'number_prefixes' => ['51-55', '500', '222100-272099'],
      'number_lengths' => [5, 10],
      'security_code_length' => 6,
      'uses_luhn' => FALSE,
    ];

    $this->type = new CreditCardType($this->definition);
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $this->assertEquals($this->definition['id'], $this->type->getId());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $this->assertEquals($this->definition['label'], $this->type->getLabel());
  }

  /**
   * @covers ::getNumberPrefixes
   */
  public function testGetNumberPrefixes() {
    $this->assertEquals($this->definition['number_prefixes'], $this->type->getNumberPrefixes());
  }

  /**
   * @covers ::getNumberLengths
   */
  public function testGetNumberLengths() {
    $this->assertEquals($this->definition['number_lengths'], $this->type->getNumberLengths(), 'Credit card type number length matches.');
  }

  /**
   * @covers ::getSecurityCodeLength
   */
  public function testGetSecurityCodeLength() {
    $this->assertEquals($this->definition['security_code_length'], $this->type->getSecurityCodeLength(), 'Credit card type security code length matches.');
  }

  /**
   * @covers ::usesLuhn
   */
  public function testUsesLuhn() {
    $this->assertEquals($this->definition['uses_luhn'], $this->type->usesLuhn());
  }

  /**
   * Tests the creation of a CreditCardType with an invalid definition.
   *
   * @dataProvider definitionProvider
   */
  public function testInvalidDefinition($definition, $message) {
    $this->setExpectedException(\InvalidArgumentException::class, $message);
    $card = new CreditCardType($definition);
  }

  /**
   * Data provider for ::testInvalidDefinition.
   *
   * @return array
   *   A list of testInvalidDefinition function arguments.
   */
  public function definitionProvider() {
    return [
      [[], 'Missing required property id.'],
      [['id' => 'llama'], 'Missing required property label.'],
      [
        [
          'id' => 'llama',
          'label' => 'Llama',
        ],
        'Missing required property number_prefixes.',
      ],
    ];
  }

}
