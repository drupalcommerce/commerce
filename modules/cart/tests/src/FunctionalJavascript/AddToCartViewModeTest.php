<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Confirms that changing the product variation keeps the same view mode.
 *
 * @group commerce
 */
class AddToCartViewModeTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

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
   * Tests changing the product variation.
   *
   * The `commerce_product_variation.default.full` configuration uses the
   * `commerce_price_plain` formatter, but the default view mode still uses the
   * `commerce_price_default` formatter. The AJAX refresh should return currency
   *  in the plain format of ##.00 USD and not $##.00.
   */
  public function testAjaxChange() {
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

    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $first_variation_price);
    $this->assertSession()->fieldValueEquals('purchased_entity[0][variation]', $this->firstVariation->id());
    $page->selectFieldOption('purchased_entity[0][variation]', $this->secondVariation->id());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $second_variation_price);

    $page->selectFieldOption('purchased_entity[0][variation]', $this->firstVariation->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('css', $price_field_selector);
    $this->assertSession()->elementTextContains('css', $price_field_selector . ' .field__item', $first_variation_price);
  }

}
