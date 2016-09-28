<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;

/**
 * Tests pages with multiple products rendered with add to cart forms.
 *
 * @group commerce
 */
class MultipleCartFormsTest extends CartBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart_test',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $products = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    for ($i = 1; $i < 5; $i++) {
      // Create a product variation.
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => (string) 3 * $i,
          'currency_code' => 'USD',
        ],
      ]);
      $this->products[] = $this->createEntity('commerce_product', [
        'type' => 'default',
        'title' => $this->randomMachineName(),
        'stores' => [$this->store],
        'variations' => [$variation],
      ]);
    }
  }

  /**
   * Tests that a page with multiple add to cart forms works properly.
   */
  public function testMultipleCartsOnPage() {
    $this->drupalGet('/test-multiple-cart-forms');

    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $this->assertEquals(5, count($forms));
    $this->submitForm([], 'Add to cart', $forms[2]->getAttribute('id'));

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertEquals(new Price('6', 'USD'), $order_items[0]->getTotalPrice());
  }

}
