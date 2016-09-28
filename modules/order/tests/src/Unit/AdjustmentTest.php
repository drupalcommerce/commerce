<?php

namespace Drupal\Tests\commerce_order\Unit;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\commerce_order\Adjustment
 * @group commerce
 */
class AdjustmentTest extends UnitTestCase {

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
      [['type' => 'discount'], 'Missing required property label'],
      [
        [
          'type' => 'discount',
          'label' => 'Test',
        ],
        'Missing required property amount',
      ],
      [
        [
          'type' => 'discount',
          'label' => 'Test',
          'amount' => '10 USD',
        ],
        sprintf('Property "amount" should be an instance of %s.', Price::class),
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
      'type' => 'discount',
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
   * @covers ::getSourceId
   */
  public function testAdjustmentMethods() {
    $definition = [
      'type' => 'discount',
      'label' => '10% off',
      'amount' => new Price('-1.00', 'USD'),
      'source_id' => '1',
    ];

    $adjustment = new Adjustment($definition);
    $this->assertEquals('discount', $adjustment->getType());
    $this->assertEquals('10% off', $adjustment->getLabel());
    $this->assertEquals('-1.00', $adjustment->getAmount()->getNumber());
    $this->assertEquals('USD', $adjustment->getAmount()->getCurrencyCode());
    $this->assertEquals('1', $adjustment->getSourceId());
  }

}
