<?php

namespace Drupal\Tests\commerce_number_pattern\Kernel\Entity;

use Drupal\commerce_number_pattern\Entity\NumberPattern;
use Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\NumberPatternInterface;
use Drupal\Tests\commerce_number_pattern\Kernel\NumberPatternKernelTestBase;

/**
 * Tests the number pattern entity.
 *
 * @coversDefaultClass \Drupal\commerce_number_pattern\Entity\NumberPattern
 * @group commerce
 */
class NumberPatternTest extends NumberPatternKernelTestBase {

  /**
   * @covers ::id
   * @covers ::label
   * @covers ::getTargetEntityTypeId
   * @covers ::setTargetEntityTypeId
   * @covers ::getPlugin
   * @covers ::getPluginId
   * @covers ::getPluginConfiguration
   */
  public function testNumberPattern() {
    $values = [
      'id' => 'test_id',
      'label' => 'Test label',
      'targetEntityType' => 'entity_test_with_store',
      'plugin' => 'monthly',
      'configuration' => [
        'pattern' => '[pattern:date:Y-m]-[pattern:number]',
        'initial_number' => 100,
        'padding' => 0,
        'per_store_sequence' => TRUE,
      ],
    ];
    $number_pattern = NumberPattern::create($values);
    $this->assertEquals('test_id', $number_pattern->id());
    $this->assertEquals('Test label', $number_pattern->label());
    $this->assertEquals($values['targetEntityType'], $number_pattern->getTargetEntityTypeId());
    $number_pattern->setTargetEntityTypeId('entity_test');
    $this->assertEquals('entity_test', $number_pattern->getTargetEntityTypeId());

    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\NumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $this->assertInstanceOf(NumberPatternInterface::class, $number_pattern_plugin);
    $this->assertEquals('monthly', $number_pattern_plugin->getPluginId());
    $this->assertEquals($number_pattern->getPluginConfiguration(), $number_pattern_plugin->getConfiguration());
    $number_pattern->setPluginConfiguration([
      'pattern' => 'INV-[current-date:custom:Y-m]-[pattern:number]',
      'padding' => 5,
    ]);
    $this->assertEquals([
      'pattern' => 'INV-[current-date:custom:Y-m]-[pattern:number]',
      'padding' => 5,
    ], $number_pattern->getPluginConfiguration());

    $number_pattern->setPluginId('yearly');
    $this->assertEquals('yearly', $number_pattern->getPluginId());
    $this->assertEmpty($number_pattern->getPluginConfiguration());
  }

}
