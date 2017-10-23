<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass Drupal\commerce_order\Adjustment
 * @group commerce
 */
class AdjustmentTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
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
    $this->setExpectedException(\InvalidArgumentException::class, $message);
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
   * Tests methods on the adjustment object.
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
   */
  public function testAdjustmentMethods() {
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
  }

}
