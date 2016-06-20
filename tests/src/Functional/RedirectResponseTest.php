<?php

namespace Drupal\Tests\commerce\Functional;

/**
 * Tests RedirectResponse.
 *
 * @group commerce
 */
class RedirectResponseTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_redirect_test'];

  /**
   * Test that forms can be redirected.
   */
  public function testThrowForm() {
    $this->drupalGet('/commerce_redirect_test/throw_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('https://www.drupal.org', $this->getSession()->getCurrentUrl());
  }

}
