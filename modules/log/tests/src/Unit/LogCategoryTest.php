<?php

namespace Drupal\Tests\commerce_log\Unit;

use Drupal\commerce_log\Plugin\LogCategory\LogCategory;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_log\Plugin\LogCategory\LogCategory
 * @group commerce
 */
class LogCategoryTest extends UnitTestCase {

  /**
   * The log category.
   *
   * @var \Drupal\commerce_log\Plugin\LogCategory\LogCategoryInterface
   */
  protected $logCategory;

  /**
   * The plugin definition array.
   *
   * @var array
   */
  protected $definition = [
    'id' => 'entity_test',
    'label' => 'Entity Test',
    'entity_type' => 'entity_test',
    'provider' => 'commerce_log_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->logCategory = new LogCategory([], 'order', $this->definition);
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $this->assertEquals($this->definition['id'], $this->logCategory->getId());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $this->assertEquals($this->definition['label'], $this->logCategory->getLabel());
  }

  /**
   * @covers ::getEntityTypeId
   */
  public function testGetEntityTypeId() {
    $this->assertEquals($this->definition['entity_type'], $this->logCategory->getEntityTypeId());
  }

}
