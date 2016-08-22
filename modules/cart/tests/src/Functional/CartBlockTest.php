<?php

namespace Drupal\Tests\commerce_cart\Functional;

/**
 * Tests the cart block.
 *
 * @group commerce
 */
class CartBlockTest extends CartBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->placeBlock('commerce_cart');
  }

  /**
   * Tests the count text (display, plurality).
   */
  public function testCountText() {
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('0 items');

    $this->cartManager->addEntity($this->cart, $this->variation);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('1 item');

    $this->cartManager->addEntity($this->cart, $this->variation, 2);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('3 items');
  }

}
