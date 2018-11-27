<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests AJAX field replacement on the add to cart form.
 *
 * @group commerce
 */
class AddToCartFieldReplacementTest extends CartWebDriverTestBase {

  /**
   * The first product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $firstVariation;

  /**
   * The second product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $secondVariation;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Use the title widget so that we do not need to use attributes.
    $order_item_form_display = EntityFormDisplay::load('commerce_order_item.default.add_to_cart');
    $order_item_form_display->setComponent('purchased_entity', [
      'type' => 'commerce_product_variation_title',
    ]);
    $order_item_form_display->save();

    // Create an optional field that will have a value only on 1 variation.
    FieldStorageConfig::create([
      'field_name' => 'field_number',
      'entity_type' => 'commerce_product_variation',
      'type' => 'integer',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_number',
      'entity_type' => 'commerce_product_variation',
      'bundle' => 'default',
      'settings' => [],
    ])->save();

    // Set up the Full view modes.
    EntityViewMode::create([
      'id' => 'commerce_product.full',
      'label' => 'Full',
      'targetEntityType' => 'commerce_product',
    ])->save();
    EntityViewMode::create([
      'id' => 'commerce_product_variation.full',
      'label' => 'Full',
      'targetEntityType' => 'commerce_product_variation',
    ])->save();

    // Use a different price widget for the two displays, to use that as
    // an indicator of the right view mode being used.
    $default_view_display = EntityViewDisplay::load('commerce_product_variation.default.default');
    if (!$default_view_display) {
      $default_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_variation',
        'bundle' => 'default',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $default_view_display->setComponent('price', [
      'type' => 'commerce_price_default',
    ]);
    $default_view_display->save();
    $full_view_display = EntityViewDisplay::load('commerce_product_variation.default.full');
    if (!$full_view_display) {
      $full_view_display = EntityViewDisplay::create([
        'targetEntityType' => 'commerce_product_variation',
        'bundle' => 'default',
        'mode' => 'full',
        'status' => TRUE,
      ]);
    }
    $full_view_display->setComponent('field_number', [
      'type' => 'number_integer',
    ]);
    $full_view_display->setComponent('price', [
      'type' => 'commerce_price_plain',
    ]);
    $full_view_display->save();

    $this->firstVariation = $this->createEntity('commerce_product_variation', [
      'title' => 'First variation',
      'type' => 'default',
      'sku' => 'first-variation',
      'price' => [
        'number' => 10,
        'currency_code' => 'USD',
      ],
      'field_number' => 202,
    ]);
    $this->secondVariation = $this->createEntity('commerce_product_variation', [
      'title' => 'Second variation',
      'type' => 'default',
      'sku' => 'second-variation',
      'price' => [
        'number' => 20,
        'currency_code' => 'USD',
      ],
    ]);
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Test product',
      'stores' => [$this->store],
      'variations' => [$this->firstVariation, $this->secondVariation],
    ]);
  }

  /**
   * Tests the field replacement.
   *
   * Expectations:
   * 1) The initial view mode is preserved on AJAX refresh.
   * 2) Optional fields are correctly replaced even if the field is empty.
   */
  public function testFieldReplacement() {
    $this->drupalGet($this->product->toUrl());

    $page = $this->getSession()->getPage();
    $renderer = $this->container->get('renderer');

    $first_variation_price = [
      '#theme' => 'commerce_price_plain',
      '#number' => $this->firstVariation->getPrice()->getNumber(),
      '#currency' => Currency::load($this->firstVariation->getPrice()->getCurrencyCode()),
    ];
    $first_variation_price = trim($renderer->renderPlain($first_variation_price));
    $second_variation_price = [
      '#theme' => 'commerce_price_plain',
      '#number' => $this->secondVariation->getPrice()->getNumber(),
      '#currency' => Currency::load($this->secondVariation->getPrice()->getCurrencyCode()),
    ];
    $second_variation_price = trim($renderer->renderPlain($second_variation_price));

    $price_field_selector = '.product--variation-field--variation_price__' . $this->product->id();
    $integer_field_selector = '.product--variation-field--variation_field_number__' . $this->product->id();

    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementExists('css', $integer_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $first_variation_price);
    $this->assertSession()->elementTextContains('css', $integer_field_selector . ' .field__item', $this->firstVariation->get('field_number')->value);
    $this->assertSession()->fieldValueEquals('purchased_entity[0][variation]', $this->firstVariation->id());
    $page->selectFieldOption('purchased_entity[0][variation]', $this->secondVariation->id());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementExists('css', $integer_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $second_variation_price);
    $this->assertSession()->elementNotExists('css', $integer_field_selector . ' .field__item');

    $page->selectFieldOption('purchased_entity[0][variation]', $this->firstVariation->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementExists('css', $integer_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $first_variation_price);
    $this->assertSession()->elementTextContains('css', $integer_field_selector . ' .field__item', $this->firstVariation->get('field_number')->value);
  }

}
