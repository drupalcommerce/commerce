<?php

namespace Drupal\Tests\commerce_payment\Kernel;

use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\views\Views;

/**
 * Tests that a view can be made of payment methods.
 *
 * @link https://www.drupal.org/project/commerce/issues/2973141
 *
 * @group commerce
 */
class ViewsIntegrationTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_payment_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_payment');
    $this->installConfig('commerce_payment_test');
  }

  /**
   * Tests the filter plugin dependencies.
   */
  public function testFilterPluginDependenciesDontExplode() {
    $view = Views::getView('payment_methods');
    // This will cause an exception if the fix is not implemented in
    // \Drupal\commerce\Plugin\views\filter\EntityBundle::calculateDependencies.
    $view->save();

    // Verify the module dependencies are still respected.
    $dependencies = $view->displayHandlers->get('default')->calculateDependencies();
    $this->assertEquals([
      'module' => [
        'commerce',
        'commerce_payment',
        'views',
      ],
    ], $dependencies);
  }

}
