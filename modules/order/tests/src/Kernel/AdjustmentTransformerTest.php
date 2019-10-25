<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;

/**
 * Tests the adjustment transformer.
 *
 * @coversDefaultClass \Drupal\commerce_order\AdjustmentTransformer
 *
 * @group commerce
 */
class AdjustmentTransformerTest extends OrderKernelTestBase {

  /**
   * The adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  protected $adjustmentTransformer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adjustmentTransformer = $this->container->get('commerce_order.adjustment_transformer');
  }

  /**
   * Tests adjustment combining.
   *
   * @covers ::combineAdjustments
   */
  public function testCombining() {
    $adjustments = [];
    // Adjustments 0 and 2 are supposed to be combined.
    $adjustments[0] = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('10', 'USD'),
      'source_id' => 'us_vat|default|standard',
      'percentage' => '0.1',
    ]);
    $adjustments[1] = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20', 'USD'),
      'percentage' => '0.2',
    ]);
    $adjustments[2] = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('3', 'USD'),
      'source_id' => 'us_vat|default|standard',
      'percentage' => '0.1',
    ]);
    $adjustments[3] = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('4', 'USD'),
      'source_id' => 'us_vat|default|reduced',
      'percentage' => '0.1',
    ]);
    $combined_adjustments = [];
    $combined_adjustments[0] = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('13', 'USD'),
      'source_id' => 'us_vat|default|standard',
      'percentage' => '0.1',
    ]);
    $combined_adjustments[1] = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20', 'USD'),
      'percentage' => '0.2',
    ]);
    $combined_adjustments[2] = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('4', 'USD'),
      'source_id' => 'us_vat|default|reduced',
      'percentage' => '0.1',
    ]);

    $result = $this->adjustmentTransformer->combineAdjustments($adjustments);
    $this->assertCount(3, $result);
    $this->assertEquals($combined_adjustments, $result);
  }

  /**
   * Tests adjustment sorting.
   *
   * @covers ::sortAdjustments
   */
  public function testSorting() {
    $first_adjustment = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('10', 'USD'),
      'percentage' => '0.1',
    ]);
    $second_adjustment = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20', 'USD'),
      'percentage' => '0.2',
    ]);

    $adjustments = $this->adjustmentTransformer->sortAdjustments([$first_adjustment, $second_adjustment]);
    $this->assertEquals([$second_adjustment, $first_adjustment], $adjustments);
  }

  /**
   * Tests adjustment rounding.
   *
   * @covers ::roundAdjustments
   * @covers ::roundAdjustment
   */
  public function testRounding() {
    $first_adjustment = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('10.489', 'USD'),
      'percentage' => '0.1',
    ]);
    $second_adjustment = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20.555', 'USD'),
    ]);
    $first_rounded_adjustment = new Adjustment([
      'type' => 'tax',
      'label' => 'VAT',
      'amount' => new Price('10.49', 'USD'),
      'percentage' => '0.1',
    ]);
    $second_rounded_adjustment = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20.56', 'USD'),
    ]);
    $second_rounded_down_adjustment = new Adjustment([
      'type' => 'promotion',
      'label' => '20% off',
      'amount' => new Price('20.55', 'USD'),
    ]);

    $adjustments = $this->adjustmentTransformer->roundAdjustments([$first_adjustment, $second_adjustment]);
    $this->assertEquals([$first_rounded_adjustment, $second_rounded_adjustment], $adjustments);

    $adjustment = $this->adjustmentTransformer->roundAdjustment($first_adjustment);
    $this->assertEquals($first_rounded_adjustment, $adjustment);

    $adjustment = $this->adjustmentTransformer->roundAdjustment($second_adjustment);
    $this->assertEquals($second_rounded_adjustment, $adjustment);

    // Confirm that the $mode is passed along.
    $adjustments = $this->adjustmentTransformer->roundAdjustments([$second_adjustment], PHP_ROUND_HALF_DOWN);
    $this->assertEquals([$second_rounded_down_adjustment], $adjustments);

    $adjustment = $this->adjustmentTransformer->roundAdjustment($second_adjustment, PHP_ROUND_HALF_DOWN);
    $this->assertEquals($second_rounded_down_adjustment, $adjustment);
  }

}
