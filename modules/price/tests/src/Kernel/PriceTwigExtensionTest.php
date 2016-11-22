<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the price twig filter.
 *
 * @group commerce
 */
class PriceTwigExtensionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'commerce',
    'commerce_price',
    'commerce_price_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->container->get('commerce_price.currency_importer')->import('USD');
  }

  /**
   * Tests an improperly formatted price array.
   */
  public function testBrokenPrice() {
    $theme = ['#theme' => 'broken_commerce_price'];
    $this->setExpectedException('InvalidArgumentException');
    $this->render($theme);
  }

  /**
   * Tests a properly formatted price array.
   */
  public function testWorkingPrice() {
    $theme = ['#theme' => 'working_commerce_price'];
    $this->render($theme);
    $this->assertText('$9.99');
  }

  /**
   * Tests a price object.
   */
  public function testPriceObject() {
    $theme = ['#theme' => 'commerce_price_object'];
    $this->render($theme);
    $this->assertText('$9.99');
  }

}
