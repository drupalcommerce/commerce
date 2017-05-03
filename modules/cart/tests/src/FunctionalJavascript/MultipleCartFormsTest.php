<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests pages with multiple products rendered with add to cart forms.
 *
 * @todo The file with the same name exists in the Functional namespace and it
 * should be decided which one to leave for this kind of test.
 *
 * @group commerce
 */
class MultipleCartFormsTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);

    // The matrix is intentionally uneven, blue / large is missing.
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];

    $price_number_matrix = [
      1 => [
        [1 => '1'],
        [2 => '2'],
        [3 => '3'],
        [4 => '4'],
        [5 => '5'],
      ],
      2 => [
        [1 => '6'],
        [2 => '7'],
        [3 => '8'],
        [4 => '9'],
        [5 => '10'],
      ],
      3 => [
        [1 => '11'],
        [2 => '12'],
        [3 => '13'],
        [4 => '14'],
        [5 => '15'],
      ],
      4 => [
        [1 => '16'],
        [2 => '17'],
        [3 => '18'],
        [4 => '19'],
        [5 => '20'],
      ],
      5 => [
        [1 => '21'],
        [2 => '22'],
        [3 => '23'],
        [4 => '24'],
        [5 => '25'],
      ],
    ];

    for ($i = 1; $i < 6; $i++) {
      // Generate products with variations off of the attributes values matrix.
      $j = 0;
      $variations = [];
      foreach ($attribute_values_matrix as $key => $value) {
        $variation = $this->createEntity('commerce_product_variation', [
          'type' => $variation_type->id(),
          'sku' => $this->randomMachineName(),
          'price' => new Price($price_number_matrix[$i][$j][$j + 1], 'USD'),
          'attribute_color' => $color_attributes[$value[0]],
          'attribute_size' => $size_attributes[$value[1]],
        ]);
        $variations[] = $variation;
        $j++;
      }
      if ($i == 1) {
        $product = $this->variation->getProduct();
        $product->setVariations($variations);
        $product->updateOriginalvalues();
        $product->save();
        $this->products[] = $product;
      }
      else {
        $this->products[] = $this->createEntity('commerce_product', [
          'type' => 'default',
          'title' => $this->randomMachineName(),
          'stores' => [$this->store],
          'variations' => $variations,
        ]);
      }
    }
  }

  /**
   * Tests that a page with multiple add to cart forms works properly.
   */
  public function testMultipleCartsOnPage() {
    // The matrix to change values on Add to cart forms in the given offset
    // order. Don't use Red and Small values as they are selected by default.
    $offset_matrix = [
      3 => ['size' => 'Large'],
      1 => ['color' => 'Blue'],
      0 => ['size' => 'Medium'],
      2 => ['color' => 'Blue'],
      4 => ['size' => 'Large'],
    ];

    foreach ($offset_matrix as $offset => $attribute) {
      $this->drupalGet('/test-multiple-cart-forms');
      /** @var \Behat\Mink\Element\NodeElement[] $forms */
      $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
      $this->assertCount(5, $forms, 'Displayed 5 Add to cart forms.');
      $values = $this->addProductVariationToCart($forms, $offset, $attribute);
      // Assert expected form values before and after submission.
      $this->assertAddToCartFormValues($values);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addProductVariationToCart(array $forms, $offset = 0, $attribute = ['size' => 'Medium']) {
    $init = $this->getAddToCartFormValues($forms[$offset]);
    $init['count'] = count($forms);
    $field = array_keys($attribute)[0];
    $label = reset($attribute);

    // Change the attribute option to trigger the AJAX form reloading.
    $forms[$offset]->selectFieldOption("purchased_entity[0][attributes][attribute_{$field}]", $label);
    $this->waitForAjaxToFinish();
    // Extract updated form values.
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $after = $this->getAddToCartFormValues($forms[$offset]);
    $this->submitForm([], 'Add to cart', $after['form_id']);

    return [
      'init' => $init,
      'after' => $after,
      'offset' => $offset,
      'field' => $field,
      'label' => $label,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function assertAddToCartFormValues(array $values) {
    $this->drupalGet('cart');
    $cart = $this->getSession()->getPage()->find('css', '[id^="views-form-commerce-cart-form-default"]');
    $this->assertTrue(is_object($cart), 'The Shopping cart is displayed.');
    $cart = $this->getShoppingCartValues($cart);
    $cart = end($cart);
    $label = ucfirst($values['field']);
    $init = $values['init']['attributes'][$label]['chosen'];
    $after = $values['after']['attributes'][$label]['chosen'];

    // Ensure we are on the same product where we had triggered an AJAX action.
    $this->assertSame($values['init']['product_id'], $values['after']['product_id'], 'The product ID is the same.');
    // Both titles must be the same.
    $this->assertSame($cart['title'], $values['after']['title'], 'The Shopping cart title and Add to cart title are equal.');
    // All the other values are expected being replaced by new ones.
    $this->assertNotEquals($values['init']['form_id'], $values['after']['form_id'], 'The Add to cart form ID is changed.');
    $this->assertNotEquals($values['init']['title'], $values['after']['title'], 'The Add to cart form title is changed.');
    $this->assertNotEquals($values['init']['sku'], $values['after']['sku'], 'The variation SKU is changed.');
    $this->assertNotEquals($values['init']['price'], $values['after']['price'], 'The variation price is changed.');
    $this->assertNotEquals($init[0]['label'], $after[0]['label'], "The {$label} attribute label is changed.");
    $this->assertNotEquals($init[0]['value'], $after[0]['value'], "The {$label} attribute value is changed.");
    // Prices on the Shopping cart page are formatted without decimals ($99).
    // Extract price number value to recreate object and properly compare then.
    $cart['price'] = new Price(ltrim($cart['price'], '$'), 'USD');
    $values['after']['price'] = new Price(ltrim($values['after']['price'], '$'), 'USD');
    $this->assertTrue($cart['price']->equals($values['after']['price']), 'The Shopping cart price and Add to cart price are equal.');
  }

}
