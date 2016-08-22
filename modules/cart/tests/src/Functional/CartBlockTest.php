<?php

namespace Drupal\Tests\commerce_cart\Functional;

/**
 * Tests cart block.
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
   * Tests that block quantity text is translatable, supports plurality.
   */
  public function testPlurality() {
    $this->drupalGet('<front>');
    // Should say we have no items.
    $this->assertSession()->pageTextContains('0 items');

    // Add a product, reload page.
    $this->cartManager->addEntity($this->cart, $this->variation);

    // We now have one item.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('1 item');

    // Add a product, reload page.
    $this->cartManager->addEntity($this->cart, $this->variation, 2);

    // We now have three items.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('3 items');
  }

}
