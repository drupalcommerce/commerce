<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Product variation entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\ProductVariation
 *
 * @group commerce
 */
class ProductVariationTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
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
   * @covers ::getOrderItemTypeId
   * @covers ::getOrderItemTitle
   * @covers ::getProduct
   * @covers ::getProductId
   * @covers ::getSku
   * @covers ::setSku
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::getPrice
   * @covers ::setPrice
   * @covers ::isActive
   * @covers ::setActive
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   * @covers ::getStores
   */
  public function testProductVariation() {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
    ]);
    $product->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
    ]);
    $variation->save();
    $product = $this->reloadEntity($product);
    $variation = $this->reloadEntity($variation);

    // Confirm that postSave() added the reference on the parent product.
    $this->assertTrue($product->hasVariation($variation));

    $this->assertEquals('default', $variation->getOrderItemTypeId());
    $this->assertEquals('My Product Title', $variation->getOrderItemTitle());

    $this->assertEquals($product, $variation->getProduct());
    $this->assertEquals($product->id(), $variation->getProductId());

    $variation->setSku('1001');
    $this->assertEquals('1001', $variation->getSku());

    $variation->setTitle('My title');
    $this->assertEquals('My title', $variation->getTitle());

    $price = new Price('9.99', 'USD');
    $variation->setPrice($price);
    $this->assertEquals($price, $variation->getPrice());

    $variation->setActive(TRUE);
    $this->assertEquals(TRUE, $variation->isActive());

    $variation->setCreatedTime(635879700);
    $this->assertEquals(635879700, $variation->getCreatedTime());

    $variation->setOwner($this->user);
    $this->assertEquals($this->user, $variation->getOwner());
    $this->assertEquals($this->user->id(), $variation->getOwnerId());
    $variation->setOwnerId(0);
    $this->assertEquals(NULL, $variation->getOwner());
    $variation->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $variation->getOwner());
    $this->assertEquals($this->user->id(), $variation->getOwnerId());

    $this->assertEquals($product->getStores(), $variation->getStores());

    // Confirm that deleting the variation deletes the reference.
    $variation->delete();
    $product = $this->reloadEntity($product);
    $this->assertFalse($product->hasVariation($variation));
  }

}
