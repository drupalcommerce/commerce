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
   * Tests the count text (display, plurality), and the cart dropdown.
   */
  public function testCartBlock() {
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('0 items');

    $this->cartManager->addEntity($this->cart, $this->variation);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('1 item');
    $this->assertSession()->pageTextContains($this->variation->getOrderItemTitle());
    $this->assertSession()->pageTextContains('1 x');

    $this->cartManager->addEntity($this->cart, $this->variation, 2);
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('3 items');
    $this->assertSession()->pageTextContains('3 x');

    // If the order is no longer a draft, the block should not render.
    $this->cart->getState()->applyTransitionById('place');
    $this->cart->save();

    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('3 items');
  }

}
