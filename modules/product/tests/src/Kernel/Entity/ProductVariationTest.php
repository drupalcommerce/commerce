<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the Product variation entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\ProductVariation
 *
 * @group commerce
 */
class ProductVariationTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'path',
    'entity',
    'entity',
    'address',
    'views',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
  ];

  /**
   * A sample store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

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
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installEntitySchema('commerce_store');
    $this->installConfig(['commerce_product']);
    $this->installConfig(['commerce_store']);

    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
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
    $variation = ProductVariation::create([
      'type' => 'default',
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);

    $product->save();

    // An initially saved product won't be the same as the loaded one.
    $product = Product::load($product->id());

    $variation->setTitle('My title');
    $this->assertEquals('My title', $variation->getTitle());

    $this->assertEquals($product, $variation->getProduct());

    $this->assertEquals($product->id(), $variation->getProductId());

    $variation->setSku('1001');
    $this->assertEquals('1001', $variation->getSku());

    $this->assertEquals(NULL, $variation->getPrice());
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
  }

  /**
   * @covers ::getOrderItemTypeId
   * @covers ::getOrderItemTitle
   * @covers ::getAttributeValueIds
   * @covers ::getAttributeValueId
   * @covers ::getAttributeValues
   * @covers ::getAttributeValue
   */
  public function testProductVariationMethods() {
    $variation = ProductVariation::create([
      'type' => 'default',
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->save();

    // An initially saved product won't be the same as the loaded one.
    $product = Product::load($product->id());

    $this->assertEquals('default', $variation->getOrderItemTypeId());

    $product->setTitle('My Product Title');
    $this->assertEquals('My Product Title', $variation->getOrderItemTitle());

    $this->assertEquals([], $variation->getAttributeValueIds());
    $this->assertEquals([], $variation->getAttributeValues());

    // @todo Create attributes, then retest attribute methods.
  }

}
