<?php

namespace Drupal\Tests\commerce_number_pattern\Kernel\Plugin\Commerce\NumberPattern;

use Drupal\commerce\Interval;
use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_number_pattern_test\Entity\EntityTestWithStore;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_number_pattern\Kernel\NumberPatternKernelTestBase;

/**
 * Tests the yearly number pattern.
 *
 * @coversDefaultClass \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\Yearly
 * @group commerce
 */
class YearlyTest extends NumberPatternKernelTestBase {

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
      'plugin' => 'yearly',
      'configuration' => [
        'per_store_sequence' => FALSE,
      ],
    ]);
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $this->assertEquals($current_date->format('Y') . '-1', $number_pattern_plugin->generate($entity));
    $this->assertEquals($current_date->format('Y') . '-2', $number_pattern_plugin->generate($entity));

    $current_sequence = $number_pattern_plugin->getCurrentSequence($entity);
    $this->assertEquals('2', $current_sequence->getNumber());
    $this->assertEquals(\Drupal::time()->getRequestTime(), $current_sequence->getGeneratedTime());
    $this->assertEquals('0', $current_sequence->getStoreId());

    $second_store = $this->createStore('Second store', 'admin2@example.com', 'online', FALSE);
    $entity->setStoreId($second_store->id());
    $entity->save();
    $this->assertEquals($current_date->format('Y') . '-3', $number_pattern_plugin->generate($entity));

    // Confirm that the sequence resets after a year.
    $interval = new Interval('1', 'year');
    $next_date = $interval->add($current_date);
    $this->rewindTime($next_date->getTimestamp());

    $number_pattern = NumberPattern::create([
      'id' => 'test',
      'plugin' => 'yearly',
      'configuration' => [
        'per_store_sequence' => FALSE,
      ],
    ]);
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $this->assertEquals($next_date->format('Y') . '-1', $number_pattern_plugin->generate($entity));
  }

}
