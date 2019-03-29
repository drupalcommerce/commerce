<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product variation access control.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductVariationAccessControlHandler
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
    'commerce_product_test',
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

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
    $regular_user = $this->createUser(['uid' => 2]);
    \Drupal::currentUser()->setAccount($regular_user);
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variation],
    ]);
    $product->save();
    $variation = $this->reloadEntity($variation);

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($variation->access('view', $account));
    $this->assertFalse($variation->access('update', $account));
    $this->assertFalse($variation->access('delete', $account));

    $account = $this->createUser([], ['view commerce_product']);
    $this->assertTrue($variation->access('view', $account));
    $this->assertFalse($variation->access('update', $account));
    $this->assertFalse($variation->access('delete', $account));

    $account = $this->createUser([], ['update any default commerce_product']);
    $this->assertFalse($variation->access('view', $account));
    $this->assertFalse($variation->access('update', $account));
    $this->assertFalse($variation->access('delete', $account));

    $account = $this->createUser([], [
      'manage default commerce_product_variation',
    ]);
    $this->assertFalse($variation->access('view', $account));
    $this->assertTrue($variation->access('update', $account));
    $this->assertTrue($variation->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_product']);
    $this->assertTrue($variation->access('view', $account));
    $this->assertTrue($variation->access('update', $account));
    $this->assertTrue($variation->access('delete', $account));

    // Broken product reference.
    $variation->set('product_id', '999');
    $account = $this->createUser([], ['manage default commerce_product_variation']);
    $this->assertFalse($variation->access('view', $account));
    $this->assertFalse($variation->access('update', $account));
    $this->assertFalse($variation->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_product_variation');

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('test', $account));

    $account = $this->createUser([], ['administer commerce_product']);
    $this->assertTrue($access_control_handler->createAccess('default', $account));

    $account = $this->createUser([], ['manage default commerce_product_variation']);
    $this->assertTrue($access_control_handler->createAccess('default', $account));
  }

  /**
   * Tests that variations without access are not available on the frontend.
   */
  public function testFrontendFiltering() {
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
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variation, $variation_denied],
    ]);
    $product->save();
    $product = $this->reloadEntity($product);

    $user = $this->createUser([], ['view commerce_product']);
    $this->container->get('current_user')->setAccount($user);

    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $variation_storage */
    $variation_storage = $this->container->get('entity_type.manager')->getStorage('commerce_product_variation');
    $this->container->get('request_stack')->getCurrentRequest()->query->set('v', $variation_denied->id());
    $context = $variation_storage->loadFromContext($product);
    $this->assertNotEquals($variation_denied->id(), $context->id());
    $this->assertEquals($variation->id(), $context->id());

    $enabled = $variation_storage->loadEnabled($product);
    $this->assertEquals(1, count($enabled));
  }

  /**
   * Tests route access for variations.
   */
  public function testRouteAccess() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
      'variations' => [$variation],
    ]);
    $product->save();
    $variation = $this->reloadEntity($variation);

    $account = $this->createUser([], ['administer commerce_product']);
    $this->assertTrue($variation->toUrl('collection')->access($account));
    $this->assertTrue($variation->toUrl('add-form')->access($account));
    $this->assertTrue($variation->toUrl('edit-form')->access($account));
    $this->assertTrue($variation->toUrl('delete-form')->access($account));

    $account = $this->createUser([], ['manage default commerce_product_variation']);
    $this->assertTrue($variation->toUrl('collection')->access($account));
    $this->assertTrue($variation->toUrl('add-form')->access($account));
    $this->assertTrue($variation->toUrl('edit-form')->access($account));
    $this->assertTrue($variation->toUrl('delete-form')->access($account));

    $account = $this->createUser([], ['access commerce_product overview']);
    $this->assertTrue($variation->toUrl('collection')->access($account));
    $this->assertFalse($variation->toUrl('add-form')->access($account));
    $this->assertFalse($variation->toUrl('edit-form')->access($account));
    $this->assertFalse($variation->toUrl('delete-form')->access($account));
  }

}
