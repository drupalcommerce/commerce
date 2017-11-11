<?php

namespace Drupal\Tests\commerce_log\Unit;

use Drupal\commerce_log\Plugin\LogTemplate\LogTemplate;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_log\Plugin\LogTemplate\LogTemplate
 * @group commerce
 */
class LogTemplateTest extends UnitTestCase {

  /**
   * The log category.
   *
   * @var \Drupal\commerce_log\Plugin\LogTemplate\LogTemplateInterface
   */
  protected $logTemplate;

  /**
   * The plugin definition array.
   *
   * @var array
   */
  protected $definition = [
    'id' => 'entity_test',
    'label' => 'Entity Test Template',
    'category' => 'entity_test_group',
    'template' => '<p>Hello {{ message }}</p>',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->logTemplate = new LogTemplate([], 'order', $this->definition);
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $this->assertEquals($this->definition['id'], $this->logTemplate->getId());
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel() {
    $this->assertEquals($this->definition['label'], $this->logTemplate->getLabel());
  }

  /**
   * @covers ::getGroup
   */
  public function getCategory() {
    $this->assertEquals($this->definition['category'], $this->logTemplate->getCategory());
  }

  /**
   * @covers ::getTemplate
   */
  public function getTemplate() {
    $this->assertEquals($this->definition['template'], $this->logTemplate->getTemplate());
  }

}
