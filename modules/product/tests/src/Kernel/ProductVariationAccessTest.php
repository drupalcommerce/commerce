<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Product variation entity.
 *
 * @group commerce
 */
class ProductVariationAccessTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'commerce_product_access_test',
  ];

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Tests that variations without access are not available.
   */
  public function testProductVariation() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation_denied */
    $variation_denied = ProductVariation::create([
      'type' => 'default',
      'sku' => 'DENY_' . $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation_denied->save();
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation2->save();

    $this->assertTrue($variation->access('view'));
    $this->assertFalse($variation_denied->access('view'));
    $this->assertTrue($variation2->access('view'));

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variation, $variation_denied, $variation2],
    ]);
    $product->save();
    $product = $this->reloadEntity($product);

    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = $this->container->get('entity_type.manager')->getStorage('commerce_product_variation');
    $this->container->get('request_stack')->getCurrentRequest()->query->set('v', $variation_denied->id());
    $context = $variation_storage->loadFromContext($product);
    $this->assertNotEquals($variation_denied->id(), $context->id());
    $this->assertEquals($variation->id(), $context->id());

    $enabled = $variation_storage->loadEnabled($product);
    $this->assertEquals(2, count($enabled));
  }

}
