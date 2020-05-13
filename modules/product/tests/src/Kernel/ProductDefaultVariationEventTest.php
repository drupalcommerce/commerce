<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the default product variation event.
 *
 * @group commerce
 */
class ProductDefaultVariationEventTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'commerce_product_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('user');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['view commerce_product']);
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['view commerce_product']);
  }

  /**
   * Tests that the event allows changing the default variation.
   */
  public function testChangeDefaultVariation() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_DEFAULT_VARIATION_EVENT',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'EXPECTED_VARIATION',
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation1->save();
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variation, $variation1],
    ]);
    $product->save();

    $default_variation = $product->getDefaultVariation();
    $this->assertEquals('EXPECTED_VARIATION', $default_variation->getSku());

    $variation->setSku('MODIFIED_SKU');
    $variation->save();

    // Reload the product to clear the defaultVariation property.
    $product = $this->reloadEntity($product);
    assert($product instanceof ProductInterface);
    $default_variation = $product->getDefaultVariation();
    $this->assertEquals('MODIFIED_SKU', $default_variation->getSku());
  }

}
