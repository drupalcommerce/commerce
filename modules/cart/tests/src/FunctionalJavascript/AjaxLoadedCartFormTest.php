<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Test add to cart forms on views with ajax pagers.
 *
 * @group commerce
 */
class AjaxLoadedCartFormTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart_test',
    'commerce_cart_big_pipe',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $colorAttributes = [];

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   */
  protected $sizeAttributes = [];

  /**
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $products = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->maximumMetaRefreshCount = 0;

    // Delete parent test product.
    $this->variation->getProduct()->setUnpublished();
    $this->variation->getProduct()->save();

    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $order_item_form_display */
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('quantity', [
      'type' => 'number',
    ]);
    $order_item_form_display->save();

    $variation_type = ProductVariationType::load('default');
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $this->colorAttributes = $color_attributes;
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $this->sizeAttributes = $size_attributes;

    $attribute_values_matrix = [
      ['red', 'small', '10.00'],
      ['red', 'medium', '10.33'],
      ['red', 'large', '10.66'],
      ['blue', 'small', '20.00'],
      ['blue', 'medium', '20.33'],
      ['blue', 'large', '20.66'],
    ];

    for ($i = 1; $i < 5; $i++) {
      // Create a product variation.
      $variations = [];
      // Generate variations off of the attributes values matrix.
      foreach ($attribute_values_matrix as $key => $value) {
        $variation = $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => $this->randomMachineName(),
          'price' => [
            'number' => $value[2],
            'currency_code' => 'USD',
          ],
          'attribute_color' => $color_attributes[$value[0]],
          'attribute_size' => $size_attributes[$value[1]],
        ]);
        $variations[] = $variation;
      }

      $this->products[] = $this->createEntity('commerce_product', [
        'type' => 'default',
        'title' => $this->randomMachineName(),
        'stores' => [$this->store],
        'variations' => $variations,
      ]);
    }
  }

  /**
   * Test add to cart forms views with ajax pagers.
   *
   * @group debug
   */
  public function testAjaxRenderedProducts() {
    // View of rendered products, each containing an add to cart form.
    $this->drupalGet('/test-ajax-paged-cart-forms');

    // Paginate View.
    $pager = $this->getSession()->getPage()->findAll('css', '.view-test-ajax-paged-cart-forms .pager__item--next');
    $pager[0]->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $initial_price = $this->getSession()->getPage()->findAll('css', '.field--type-commerce-price .field__item');
    $initial_price = $initial_price[0]->getText();

    // Changing the product attributes should not cause /views/ajax to 404.
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $current_form = $forms[0];
    $current_form->selectFieldOption('Color', 'Blue');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $forms[1]->selectFieldOption('Size', 'Large');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that price changed with new attribute selection.
    $updated_price = $this->getSession()->getPage()->findAll('css', '.field--type-commerce-price .field__item');
    $updated_price = $updated_price[0]->getText();
    $this->assertNotEqual($initial_price, $updated_price);
  }

}
