<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_log\Entity\Log;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests log generation.
 *
 * @group commerce
 */
class LogTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'commerce_log',
    'commerce_log_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_log');
  }

  /**
   * Tests log generated on EntityTest create.
   */
  public function testLogTemplate() {
    $entity = EntityTest::create([
      'name' => 'Camelids',
      'type' => 'bar',
    ]);
    $entity->save();

    $log = Log::load(1);
    $this->assertNotNull($log);
    $view = $this->container->get('entity_type.manager')->getViewBuilder($log->getEntityTypeId())->view($log);
    $this->render($view);
    $this->assertText("{$entity->label()} was created.");
  }

}
