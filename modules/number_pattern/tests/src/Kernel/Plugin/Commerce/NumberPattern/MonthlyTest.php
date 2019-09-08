<?php

namespace Drupal\Tests\commerce_number_pattern\Kernel\Plugin\Commerce\NumberPattern;

use Drupal\commerce_number_pattern_test\Entity\EntityTestWithStore;
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
    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $this->pluginManager->createInstance('monthly', [
      '_entity_id' => 'test',
    ]);
    $current_date = date('Y-m');
    $this->assertEquals($current_date . '-1', $number_pattern_plugin->generate($entity));
    $this->assertEquals($current_date . '-2', $number_pattern_plugin->generate($entity));

    // Confirm that the sequence resets after a month.
    $this->rewindTime(strtotime('+1 month'));
    $next_month = date('m') + 1;
    $expected_date = date('Y') . '-' . $next_month;
    $number_pattern_plugin = $this->pluginManager->createInstance('monthly', [
      '_entity_id' => 'test',
    ]);
    $this->assertEquals($expected_date . '-1', $number_pattern_plugin->generate($entity));
  }

}
