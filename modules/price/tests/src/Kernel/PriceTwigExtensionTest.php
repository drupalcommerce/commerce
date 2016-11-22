<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the price twig filter.
 *
 * @group commerce
 */
class PriceTwigExtensionTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price_test',
  ];

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
