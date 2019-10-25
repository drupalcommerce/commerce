<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;

/**
 * @coversDefaultClass \Drupal\commerce_order\Adjustment
 * @group commerce
 */
class AdjustmentTest extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order_test',
  ];

  /**
   * Tests the constructor and definition checks.
   *
   * @covers ::__construct
   *
   * @dataProvider invalidDefinitionProvider
   */
  public function testInvalidAdjustmentConstruct($definition, $message) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    new Adjustment($definition);
  }

  /**
   * Invalid constructor definitions.
   *
   * @return array
   *   The definitions.
   */
  public function invalidDefinitionProvider() {
    return [
      [[], 'Missing required property type'],
      [['type' => 'custom'], 'Missing required property label'],
      [
        [
          'type' => 'custom',
          'label' => 'Test',
        ],
        'Missing required property amount',
      ],
      [
        [
          'type' => 'custom',
          'label' => 'Test',
          'amount' => '10 USD',
        ],
        sprintf('Property "amount" should be an instance of %s.', Price::class),
      ],
      [
        [
          'type' => 'foo',
          'label' => 'Foo',
          'amount' => new Price('1.00', 'USD'),
        ],
        'foo is an invalid adjustment type.',
      ],
      [
        [
          'type' => 'custom',
          'label' => 'Foo',
          'amount' => new Price('1.00', 'USD'),
          'percentage' => 0.1,
        ],
        'The provided percentage "0.1" must be a string, not a float.',
      ],
      [
        [
          'type' => 'custom',
          'label' => 'Foo',
          'amount' => new Price('1.00', 'USD'),
          'percentage' => 'INVALID',
        ],
        'The provided percentage "INVALID" is not a numeric value.',
      ],
    ];
  }

  /**
   * Tests the constructor and definition checks.
   *
   * @covers ::__construct
   */
  public function testValidAdjustmentConstruct() {
    $definition = [
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'source_id' => '1',
    ];

    $adjustment = new Adjustment($definition);
    $this->assertInstanceOf(Adjustment::class, $adjustment);
  }

  /**
   * Tests getters.
   *
   * @covers ::getType
   * @covers ::getLabel
   * @covers ::getAmount
   * @covers ::isPositive
   * @covers ::isNegative
   * @covers ::getPercentage
   * @covers ::getSourceId
   * @covers ::isIncluded
   * @covers ::isLocked
   * @covers ::toArray
   */
  public function testGetters() {
    $definition = [
      'type' => 'custom',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ];

    $adjustment = new Adjustment($definition);
    $this->assertEquals('custom', $adjustment->getType());
    $this->assertEquals('10% off', $adjustment->getLabel());
    $this->assertEquals('-1.00', $adjustment->getAmount()->getNumber());
    $this->assertEquals('USD', $adjustment->getAmount()->getCurrencyCode());
    $this->assertFalse($adjustment->isPositive());
    $this->assertTrue($adjustment->isNegative());
    $this->assertEquals('0.1', $adjustment->getPercentage());
    $this->assertEquals('1', $adjustment->getSourceId());
    $this->assertTrue($adjustment->isIncluded());
    $this->assertTrue($adjustment->isLocked());
    $this->assertEquals($definition, $adjustment->toArray());
  }

  /**
   * Tests the arithmetic operators.
   *
   * @covers ::add
   * @covers ::subtract
   * @covers ::multiply
   * @covers ::divide
   */
  public function testArithmetic() {
    $first_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('2.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);
    $second_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('3.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);
    $third_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('5.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);
    $fourth_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('6.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);

    $this->assertEquals($third_adjustment, $first_adjustment->add($second_adjustment));
    $this->assertEquals($second_adjustment, $third_adjustment->subtract($first_adjustment));
    $this->assertEquals($fourth_adjustment, $second_adjustment->multiply('2'));
    $this->assertEquals($first_adjustment, $fourth_adjustment->divide('3'));
  }

  /**
   * @covers ::add
   */
  public function testMismatchedTypes() {
    $first_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('2.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);
    $second_adjustment = new Adjustment([
      'type' => 'promotion',
      'amount' => new Price('3.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Adjustment type "promotion" does not match "custom".');
    $first_adjustment->add($second_adjustment);
  }

  /**
   * @covers ::add
   */
  public function testMismatchedSourceIds() {
    $first_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('2.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '1',
      'included' => TRUE,
      'locked' => TRUE,
    ]);
    $second_adjustment = new Adjustment([
      'type' => 'custom',
      'amount' => new Price('3.00', 'USD'),
      'label' => '10% off',
      'percentage' => '0.1',
      'source_id' => '2',
      'included' => TRUE,
      'locked' => TRUE,
    ]);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Adjustment source ID "2" does not match "1".');
    $first_adjustment->add($second_adjustment);
  }

}
