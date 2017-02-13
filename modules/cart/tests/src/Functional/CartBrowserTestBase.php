<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;
use Behat\Mink\Element\NodeElement as BehatNodeElement;

/**
 * Defines base class for commerce_cart test cases.
 */
abstract class CartBrowserTestBase extends OrderBrowserTestBase {

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_cart',
    'commerce_cart_test',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product',
      'access content',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createStore();

    $this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store);
    $this->cartManager = \Drupal::service('commerce_cart.cart_manager');
    $this->attributeFieldManager = \Drupal::service('commerce_product.attribute_field_manager');
  }

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
   * Creates an attribute field and set of attribute values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   * @param string $name
   *   The attribute field name.
   * @param array $options
   *   Associative array of key name values. [red => Red].
   * @param bool $test_field
   *   Flag to create a test field on the attribute.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet(ProductVariationTypeInterface $variation_type, $name, array $options, $test_field = FALSE) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type->id());

    if ($test_field) {
      $field_storage = FieldStorageConfig::loadByName('commerce_product_attribute_value', 'rendered_test');
      if (!$field_storage) {
        $field_storage = FieldStorageConfig::create([
          'field_name' => 'rendered_test',
          'entity_type' => 'commerce_product_attribute_value',
          'type' => 'text',
        ]);
        $field_storage->save();
      }

      FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $attribute->id(),
      ])->save();

      /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $attribute_view_display */
      $attribute_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_attribute_value',
        'bundle' => $name,
        'mode' => 'add_to_cart',
        'status' => TRUE,
      ]);
      $attribute_view_display->removeComponent('name');
      $attribute_view_display->setComponent('rendered_test', [
        'label' => 'hidden',
        'type' => 'string',
      ]);
      $attribute_view_display->save();
    }

    $attribute_set = [];
    foreach ($options as $key => $value) {
      $attribute_set[$key] = $this->createAttributeValue($name, $value);
    }

    return $attribute_set;
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute ID.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The attribute value entity.
   */
  protected function createAttributeValue($attribute, $name) {
    $attribute_value = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
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

  /**
   * Helper method to fetch values from an Add to Cart form displayed on a page.
   *
   * Works with the following variation attribute form display widgets: select
   * list, radio buttons, rendered attribute and variation titles select list.
   *
   * @param \Behat\Mink\Element\NodeElement $form
   *   The Add to Cart form object.
   *
   * @return array
   *   The array of values:
   *   - "product_id": The form parent product ID.
   *   - "form_id": The Add to cart form ID.
   *   - "title": The selected variation title.
   *   - "sku": The selected variation SKU.
   *   - "price": The selected variation formatted price.
   *   - "attributes": Attributes labels and values keyed by a field label:
   *     - "ignored"|"chosen": The (not) selected/(un)checked attribute data.
   *       - "label": The label for an attribute.
   *       - "value": The ID of an attribute.
   *
   * @see \Drupal\Tests\commerce_cart\FunctionalJavascript\MultipleCartFormsTest->testMultipleCartsOnPage()
   */
  protected function getAddToCartFormValues(BehatNodeElement $form) {
    $values = [];
    $values['product_id'] = [];
    $values['form_id'] = $form->getAttribute('id');
    $grand_parent = $form->getParent()->getParent();
    $title = $grand_parent->find('css', '[class^="product--variation-field--variation_title__"]');
    $values['title'] = is_object($title) ? $title->getText() : '';
    $sku = $grand_parent->find('css', '[class^="product--variation-field--variation_sku__"]');
    $values['sku'] = is_object($sku) ? $sku->getText() : '';
    if (!empty($values['sku'])) {
      foreach (explode(' ', $sku->getAttribute('class')) as $class) {
        $parts = explode('product--variation-field--variation_sku__', $class);
        if (count($parts) == 2 && is_numeric($parts[1])) {
          $values['product_id'] = $parts[1];
        }
      }
    }
    $price = $grand_parent->find('css', '[class^="product--variation-field--variation_price__"]');
    $values['price'] = is_object($price) ? $price->find('css', '.field__item')->getText() : '';
    $attributes = $form->find('css', '[id^="edit-purchased-entity-0-attributes"]') ?: $form->find('css', '.form-item-purchased-entity-0-variation');
    $values['attributes'] = [];
    if (is_object($attributes)) {
      $attributes = $attributes->findAll('css', '[id^="edit-purchased-entity-0-attributes-attribute-"]');
      $attributes = $attributes ?: $attributes->findAll('css', '[id^="edit-purchased-entity-0-variation"]');
      if (!empty($attributes)) {
        foreach ($attributes as $attribute) {
          $element = $attribute->getTagName();
          if ($element == 'select') {
            $parent = $attribute->getParent()->find('css', '[for^="edit-purchased-entity-0-attributes-attribute-"]');
            $parent = $parent ?: $attributes[0]->getParent()->find('css', '[for^="edit-purchased-entity-0-variation"]');
            $field_label = $parent->getText();
            foreach ($attribute->findAll('named', ['option', '']) as $option) {
              $values['attributes'][$field_label][$option->isSelected() ? 'chosen' : 'ignored'][] = [
                'label' => $option->getText(),
                'value' => $option->getValue(),
              ];
            }
          }
          elseif ($element == 'fieldset' && $legend = $attribute->find('css', '.fieldset-legend')) {
            $field_label = $legend->getText();
            $checked = $attribute->find('css', '.form-radios')->find('css', 'input[checked="checked"]');
            foreach ($attribute->findAll('named', ['radio', '']) as $radio) {
              $values['attributes'][$field_label][$radio->isChecked() ? 'chosen' : 'ignored'][] = [
                'label' => $radio->getParent()->find('css', '[for^="edit-purchased-entity-0-attributes-attribute-"]')->getText(),
                'value' => $radio->getAttribute('value'),
              ];
            }
          }
        }
      }
    }

    return $values;
  }

  /**
   * Helper method to fetch values from the shopping cart displayed on a page.
   *
   * @param \Behat\Mink\Element\NodeElement $cart
   *   The Shopping Cart form object.
   *
   * @return array
   *   The array of order items having the following values:
   *   - "title": The title of an order item.
   *   - "quantity": The quantity of an order item.
   *   - "price": The price of an order item.
   *   - "total": The total of an order item.
   *
   * @see \Drupal\Tests\commerce_cart\FunctionalJavascript\MultipleCartFormsTest->assertAddToCartFormValues()
   */
  protected function getShoppingCartValues(BehatNodeElement $cart) {
    $order_items = [];
    foreach ($cart->findAll('css', 'td.views-field-purchased-entity') as $item) {
      $row = $item->getParent();
      $order_items[] = [
        'title' => $row->find('css', '.field--name-title')->getText(),
        'quantity' => $row->find('css', '[id^="edit-edit-quantity"]')->getValue(),
        'price' => $row->find('css', '.views-field-unit-price__number')->getText(),
        'total' => $row->find('css', '.views-field-total-price__number')->getText(),
      ];
    }

    return $order_items;
  }

}
