<?php

namespace Drupal\Tests\commerce_number_pattern\Kernel\Plugin\Commerce\NumberPattern;

use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_number_pattern_test\Entity\EntityTestWithStore;
use Drupal\Tests\commerce_number_pattern\Kernel\NumberPatternKernelTestBase;

/**
 * Tests the infinite number pattern.
 *
 * @coversDefaultClass \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\Infinite
 * @group commerce
 */
class InfiniteTest extends NumberPatternKernelTestBase {

  /**
   * @covers ::generate
   * @covers ::getInitialSequence
   * @covers ::getCurrentSequence
   * @covers ::getNextSequence
   * @covers ::resetSequence
   */
  public function testGenerate() {
    $entity = EntityTestWithStore::create([
      'store_id' => $this->store,
    ]);
    $entity->save();

    $number_pattern = NumberPattern::create([
      'id' => 'test',
      'plugin' => 'infinite',
      'configuration' => [
        'padding' => 0,
        'pattern' => 'INV-[pattern:number]',
        'per_store_sequence' => TRUE,
        'initial_number' => 1000,
      ],
    ]);
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $initial_sequence = $number_pattern_plugin->getInitialSequence($entity);
    $this->assertEquals('1000', $initial_sequence->getNumber());
    $this->assertEquals(\Drupal::time()->getRequestTime(), $initial_sequence->getGeneratedTime());
    $this->assertEquals($this->store->id(), $initial_sequence->getStoreId());
    $this->assertNull($number_pattern_plugin->getCurrentSequence($entity));

    $this->assertEquals('INV-1000', $number_pattern_plugin->generate($entity));
    $this->assertEquals('INV-1001', $number_pattern_plugin->generate($entity));
    $current_sequence = $number_pattern_plugin->getCurrentSequence($entity);
    $this->assertEquals('1001', $current_sequence->getNumber());
    $this->assertEquals(\Drupal::time()->getRequestTime(), $current_sequence->getGeneratedTime());
    $this->assertEquals($this->store->id(), $current_sequence->getStoreId());

    // Confirm that the sequence can be reset.
    $number_pattern_plugin->resetSequence();
    $this->assertNull($number_pattern_plugin->getCurrentSequence($entity));
    $this->assertEquals('INV-1000', $number_pattern_plugin->generate($entity));
    $this->assertEquals('INV-1001', $number_pattern_plugin->generate($entity));

    // Test the token replacement.
    $configuration = $number_pattern_plugin->getConfiguration();
    $configuration['pattern'] = 'INV-[entity_test_with_store:store_id:target_id]-[pattern:number]';
    $number_pattern_plugin->setConfiguration($configuration);
    $this->assertEquals('INV-1-1002', $number_pattern_plugin->generate($entity));

    // Confirm that each store gets its own sequence.
    $second_store = $this->createStore('Second store', 'admin2@example.com', 'online', FALSE);
    $entity->setStoreId($second_store->id());
    $entity->save();
    $this->assertEquals('INV-2-1000', $number_pattern_plugin->generate($entity));

    // Test the padding.
    $configuration = $number_pattern_plugin->getConfiguration();
    $configuration['padding'] = 4;
    $configuration['initial_number'] = 1;
    $number_pattern_plugin->setConfiguration($configuration);
    $number_pattern_plugin->resetSequence();
    $this->assertEquals('INV-2-0001', $number_pattern_plugin->generate($entity));
  }

}
