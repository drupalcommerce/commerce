<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\Config\ConfigUpdateResult;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\Config\ConfigUpdateResult
 * @group commerce
 */
class ConfigUpdaterResultTest extends UnitTestCase {

  /**
   * @covers ::getFailed
   */
  public function testGetFailed() {
    $messages = [
      'fake.config.name.one' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.two' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.three' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.four' => $this->prophesize(MarkupInterface::class),
    ];

    $result = new ConfigUpdateResult([], $messages);

    $this->assertEquals(count($result->getFailed()), 4);
  }

  /**
   * @covers ::getSucceeded
   */
  public function testGetSucceeded() {
    $messages = [
      'fake.config.name.one' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.two' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.three' => $this->prophesize(MarkupInterface::class),
      'fake.config.name.four' => $this->prophesize(MarkupInterface::class),
    ];

    $result = new ConfigUpdateResult($messages, []);

    $this->assertEquals(count($result->getSucceeded()), 4);
  }

}
