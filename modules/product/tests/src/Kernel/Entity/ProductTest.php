<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the Product entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\Product
 *
 * @group commerce
 */
class ProductTest extends EntityKernelTestBase {

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
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::isPublished
   * @covers ::setPublished
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::getStoreIds
   * @covers ::setStoreIds
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   */
  public function testProduct() {
    $product = Product::create([
      'type' => 'default',
    ]);
    $product->save();

    $product->setTitle('My title');
    $this->assertEquals('My title', $product->getTitle());

    $this->assertEquals(TRUE, $product->isPublished());
    $product->setPublished(FALSE);
    $this->assertEquals(FALSE, $product->isPublished());

    $product->setCreatedTime(635879700);
    $this->assertEquals(635879700, $product->getCreatedTime());

    $product->setStores([$this->store]);
    $this->assertEquals([$this->store], $product->getStores());
    $this->assertEquals([$this->store->id()], $product->getStoreIds());
    $product->setStores([]);
    $this->assertEquals([], $product->getStores());
    $product->setStoreIds([$this->store->id()]);
    $this->assertEquals([$this->store], $product->getStores());
    $this->assertEquals([$this->store->id()], $product->getStoreIds());

    $product->setOwner($this->user);
    $this->assertEquals($this->user, $product->getOwner());
    $this->assertEquals($this->user->id(), $product->getOwnerId());
    $product->setOwnerId(0);
    $this->assertEquals(NULL, $product->getOwner());
    $product->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $product->getOwner());
    $this->assertEquals($this->user->id(), $product->getOwnerId());
  }

  /**
   * @covers ::getVariationIds
   * @covers ::getVariations
   * @covers ::setVariations
   * @covers ::hasVariations
   * @covers ::addVariation
   * @covers ::removeVariation
   * @covers ::hasVariation
   * @covers ::getDefaultVariation
   */
  public function testVariationMethods() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation2->save();

    $product = Product::create([
      'type' => 'default',
    ]);
    $product->save();

    // An initially saved variation won't be the same as the loaded one.
    $variation1 = ProductVariation::load($variation1->id());
    $variation2 = ProductVariation::load($variation2->id());

    $variations = [$variation1, $variation2];
    $this->assertFalse($product->hasVariations());
    $product->setVariations($variations);
    $this->assertTrue($product->hasVariations());
    $variations_match = $variations == $product->getVariations();
    $this->assertTrue($variations_match);
    $variation_ids = [$variation1->id(), $variation2->id()];
    $variation_ids_match = $variation_ids == $product->getVariationIds();
    $this->assertTrue($variation_ids_match);

    $this->assertTrue($product->hasVariation($variation1));
    $product->removeVariation($variation1);
    $this->assertFalse($product->hasVariation($variation1));
    $product->addVariation($variation1);
    $this->assertTrue($product->hasVariation($variation1));

    $this->assertEquals($product->getDefaultVariation(), $variation2);
    $this->assertNotEquals($product->getDefaultVariation(), $variation1);
  }

  /**
   * Tests variation's canonical URL.
   */
  public function testCanonicalVariationLink() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation1],
    ]);
    $product->save();

    $product_url = $product->toUrl()->toString();
    $variation_url = $variation1->toUrl()->toString();
    $this->assertEquals($product_url . '?v=' . $variation1->id(), $variation_url);
  }

}
