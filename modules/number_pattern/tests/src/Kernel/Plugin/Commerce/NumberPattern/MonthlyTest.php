<?php

namespace Drupal\Tests\commerce_number_pattern\Kernel\Plugin\Commerce\NumberPattern;

use Drupal\commerce\Interval;
use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_number_pattern_test\Entity\EntityTestWithStore;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_number_pattern\Kernel\NumberPatternKernelTestBase;

/**
 * Tests the monthly number pattern.
 *
 * @coversDefaultClass \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\Monthly
 * @group commerce
 */
class MonthlyTest extends NumberPatternKernelTestBase {

  /**
   * @covers ::generate
   */
  public function testGenerate() {
    $current_date = new DrupalDateTime();
    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    $number_pattern = NumberPattern::create([
      'id' => 'test',
      'plugin' => 'monthly',
      'configuration' => [],
    ]);
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $this->assertEquals($current_date->format('Y-m') . '-1', $number_pattern_plugin->generate($entity));
    $this->assertEquals($current_date->format('Y-m') . '-2', $number_pattern_plugin->generate($entity));

    // Confirm that the sequence resets after a month.
    $interval = new Interval('1', 'month');
    $next_date = $interval->add($current_date);
    $this->rewindTime($next_date->getTimestamp());

    $number_pattern = NumberPattern::create([
      'id' => 'test',
      'plugin' => 'monthly',
      'configuration' => [],
    ]);
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $this->assertEquals($next_date->format('Y-m') . '-1', $number_pattern_plugin->generate($entity));
  }

}
