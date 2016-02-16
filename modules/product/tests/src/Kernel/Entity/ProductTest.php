<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Entity\StoreType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the Product entity.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\Product
 *
 * @group commerce
 */
class ProductTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'profile',
    'state_machine',
    'commerce_order',
    'system',
    'field',
    'options',
    'user',
    'path',
    'text',
    'filter',
    'entity',
    'entity_test',
    'address',
    'views',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
  ];

  protected $variation;
  protected $variationType;
  protected $productType;
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);

    // Create a variation.
    $this->variation = ProductVariation::create([
      'sku' => 'AZAE1252',
      'status' => TRUE,
      'price' => 9.99,
      'variation_id' => 12,
      'type' => 'default',
    ]);
    $this->variation->save();

    // Create a product type.
    $this->productType = ProductType::create([
      'label' => 'default',
      'id' => 1,
      'variationType' => 'default',
      'description' => 'test',
    ]);
    $this->productType->save();

    // Create a user.
    $this->user = User::create([
      'name' => 'test',
      'uid' => 1,
      'status' => TRUE,
    ]);
    $this->user->save();
  }

  /**
   * Tests default variation.
   *
   * @covers ::getVariations
   * @covers ::setVariations
   * @covers ::hasVariations
   * @covers ::addVariation
   * @covers ::removeVariation
   * @covers ::hasVariation
   * @covers ::getDefaultVariation
   */
  public function testGetDefaultVariation() {
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

    $this->assertTrue($product->hasVariation($variation1));
    $product->removeVariation($variation1);
    $this->assertFalse($product->hasVariation($variation1));
    $product->addVariation($variation1);
    $this->assertTrue($product->hasVariation($variation1));

    $this->assertEquals($product->getDefaultVariation(), $variation2);
    $this->assertNotEquals($product->getDefaultVariation(), $variation1);
  }

  /**
   * Tests getters and setters for the Product interface.
   *
   * @covers ::getStores
   * @covers ::setStores
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::isPublished
   * @covers ::setPublished
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getOwner
   * @covers ::getOwnerId
   * @covers ::setOwner
   * @covers ::setOwnerId
   */
  public function testProduct() {
    // Create a store type.
    $storeType = StoreType::create([
      'id' => 'StoreType',
      'label' => 'Store Type',
    ]);
    $storeType->save();

    // Create a store.
    $store = Store::create([
      'type' => $storeType->id(),
      'name' => 'My fancy store',
    ]);
    $store->save();

    // Add the reference field to the Product type.
    commerce_product_add_stores_field($this->productType);
    commerce_product_add_variations_field($this->productType);

    // Create a product.
    $product = Product::create([
      'title' => 'Test Product',
      'variations' => [$this->variation],
      'body' => 'Longer text then the title',
      'status' => TRUE,
      'type' => $this->productType->id(),
      'created' => 1454515675,
      'stores' => [$store],
      'uid' => $this->user->id(),
    ]);
    $product->save();

    // Product: Check if the store has been correctly set.
    $productStores = $product->getStores();
    $this->assertEquals($productStores[0]->id(), $store->id());

    // Create a new store.
    $newStore = Store::create([
      'type' => $storeType->id(),
      'name' => 'My fancy store',
    ]);
    $newStore->save();
    $product->setStores([$newStore]);
    $product->save();

    // Product: Check if the new store has been correctly set.
    $productStores = $product->getStores();
    $this->assertEquals($productStores[0]->id(), $newStore->id());

    // Product: Test title get and set.
    $this->assertEquals('Test Product', $product->getTitle());
    $product->setTitle('Test new title');
    $product->save();
    $this->assertEquals('Test new title', $product->getTitle());

    // Product: Published status get and set.
    $this->assertTrue($product->isPublished());
    $product->setPublished(FALSE);
    $product->save();
    $this->assertFalse($product->isPublished());

    // Product: Creation time get and set.
    $this->assertEquals(1454515675, $product->getCreatedTime());
    $timestamp_test = time();
    $product->setCreatedTime($timestamp_test);
    $product->save();
    $this->assertEquals($timestamp_test, $product->getCreatedTime());

    // Product: test getOwnerId and GetOWnder.
    $currentOwner = $product->getOwner();
    $this->assertEquals($currentOwner->id(), $this->user->id());
    $this->assertEquals($product->getOwnerId(), $this->user->id());

    // Set a new user.
    $secondUser = User::create([
      'name' => 'seconduser',
      'uid' => 2,
      'status' => TRUE,
    ]);
    $secondUser->save();

    // Product: test setOwner.
    $product->setOwner($secondUser);
    $product->save();

    // Product: test getOwnerId and GetOWnder with the new owner.
    $this->assertEquals($product->getOwner()->id(), $secondUser->id());
    $this->assertEquals($product->getOwnerId(), $secondUser->id());

    // Product: set Owner back to 1 testing setOwnerId.
    $product->setOwnerId($this->user->id());
    $product->save();

    // Product: test getOwnerId and GetOWnder with the original owner.
    $this->assertEquals($product->getOwner()->id(), $this->user->id());
    $this->assertEquals($product->getOwnerId(), $this->user->id());
  }

}
