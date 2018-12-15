<?php

namespace Drupal\Tests\commerce_cart\Traits;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Defines a trait for commerce_cart functional tests.
 */
trait CartBrowserTestTrait {

  /**
   * Posts the add to cart form for a product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param array $edit
   *   The form array.
   *
   * @throws \Exception
   */
  protected function postAddToCart(ProductInterface $product, array $edit = []) {
    $this->drupalGet('product/' . $product->id());
    $this->assertSession()->buttonExists('Add to cart');

    $this->submitForm($edit, 'Add to cart');
  }

  /**
   * Asserts that an attribute option is selected.
   *
   * @param string $selector
   *   The element selector.
   * @param string $option
   *   The option.
   */
  protected function assertAttributeSelected($selector, $option) {
    $selected_option = $this->getSession()->getPage()->find('css', 'select[name="' . $selector . '"] option[selected="selected"]')->getText();
    $this->assertEquals($option, $selected_option);
  }

  /**
   * Asserts that an attribute option does exist.
   *
   * @param string $selector
   *   The element selector.
   * @param string $option
   *   The option.
   */
  protected function assertAttributeExists($selector, $option) {
    $this->assertSession()->elementExists('xpath', '//select[@name="' . $selector . '"]//option[@value="' . $option . '"]');
  }

  /**
   * Asserts that an attribute option does not exist.
   *
   * @param string $selector
   *   The element selector.
   * @param string $option
   *   The option.
   */
  protected function assertAttributeDoesNotExist($selector, $option) {
    $this->assertSession()->elementNotExists('xpath', '//select[@name="' . $selector . '"]//option[@value="' . $option . '"]');
  }

  /**
   * Assert the order item in the order is correct.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The purchased product variation.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param int $quantity
   *   The quantity.
   */
  protected function assertOrderItemInOrder(ProductVariationInterface $variation, OrderItemInterface $order_item, $quantity = 1) {
    $this->assertEquals($order_item->getTitle(), $variation->getOrderItemTitle());
    $this->assertNotEmpty(($order_item->getQuantity() == $quantity), t('The product @product has been added to cart with quantity of @quantity.', [
      '@product' => $order_item->getTitle(),
      '@quantity' => $order_item->getQuantity(),
    ]));
  }

}
