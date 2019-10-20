<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\commerce_price\Entity\Currency;

/**
 * Tests the commerce twig filters.
 *
 * @group commerce
 */
class CommerceTwigExtensionTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * Tests trying to render a config entity.
   */
  public function testRenderConfigEntity() {
    $theme = [
      '#theme' => 'render_entity',
      '#entity' => Currency::load('USD'),
    ];
    $this->expectException('InvalidArgumentException');
    $this->render($theme);
  }

  /**
   * Tests rendering a content entity.
   */
  public function testRenderContentEntity() {
    $account = $this->createUser();

    $theme = [
      '#theme' => 'render_entity',
      '#entity' => $account,
    ];
    $this->render($theme);
    $this->assertText('Member for');
  }

}
