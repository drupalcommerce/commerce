<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\UserInterface;

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
    // Needed to confirm that url generation doesn't cause a crash when
    // deleting a product variation without a referenced product.
    'menu_link_content',
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
    $this->installEntitySchema('commerce_product_attribute_value');
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
   * @covers ::getListPrice
   * @covers ::setListPrice
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

    $list_price = new Price('19.99', 'USD');
    $variation->setListPrice($list_price);
    $this->assertEquals($list_price, $variation->getListPrice());

    $price = new Price('9.99', 'USD');
    $variation->setPrice($price);
    $this->assertEquals($price, $variation->getPrice());

    $variation->setPublished();
    $this->assertEquals(TRUE, $variation->isPublished());

    $variation->setCreatedTime(635879700);
    $this->assertEquals(635879700, $variation->getCreatedTime());

    $variation->setOwner($this->user);
    $this->assertEquals($this->user, $variation->getOwner());
    $this->assertEquals($this->user->id(), $variation->getOwnerId());
    $variation->setOwnerId(0);
    $this->assertInstanceOf(UserInterface::class, $variation->getOwner());
    $this->assertTrue($variation->getOwner()->isAnonymous());
    // Non-existent/deleted user ID.
    $variation->setOwnerId(892);
    $this->assertInstanceOf(UserInterface::class, $variation->getOwner());
    $this->assertTrue($variation->getOwner()->isAnonymous());
    $this->assertEquals(892, $variation->getOwnerId());
    $variation->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $variation->getOwner());
    $this->assertEquals($this->user->id(), $variation->getOwnerId());

    $this->assertEquals($product->getStores(), $variation->getStores());

    // Confirm that deleting the variation deletes the reference.
    $variation->delete();
    $product = $this->reloadEntity($product);
    $this->assertFalse($product->hasVariation($variation));

    // Confirm that the attribute methods return nothing by default.
    $this->assertEmpty($variation->getAttributeFieldNames());
    $this->assertEmpty($variation->getAttributeValueIds());
    $this->assertEmpty($variation->getAttributeValues());

    $this->assertEquals([
      'store',
    ], $variation->getCacheContexts());
  }

  /**
   * @covers ::getAttributeFieldNames
   * @covers ::getAttributeValueIds
   * @covers ::getAttributeValueId
   * @covers ::getAttributeValues
   * @covers ::getAttributeValue
   */
  public function testAttributes() {
    $color_attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $color_attribute->save();

    $size_attribute = ProductAttribute::create([
      'id' => 'size',
      'label' => 'Size',
    ]);
    $size_attribute->save();

    $attribute_field_manager = $this->container->get('commerce_product.attribute_field_manager');
    $attribute_field_manager->createField($color_attribute, 'default');
    $attribute_field_manager->createField($size_attribute, 'default');

    $color_attribute_value = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Blue',
      'weight' => 0,
    ]);
    $color_attribute_value->save();
    $color_attribute_value = $this->reloadEntity($color_attribute_value);

    $size_attribute_value = ProductAttributeValue::create([
      'attribute' => 'size',
      'name' => 'Medium',
      'weight' => 0,
    ]);
    $size_attribute_value->save();
    $size_attribute_value = $this->reloadEntity($size_attribute_value);

    $product = Product::create([
      'type' => 'default',
      'title' => 'My Product Title',
    ]);
    $product->save();
    $product = $this->reloadEntity($product);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
      'attribute_color' => $color_attribute_value->id(),
      'attribute_size' => $size_attribute_value->id(),
    ]);
    $variation->save();
    $variation = $this->reloadEntity($variation);

    $this->assertEquals(['attribute_color', 'attribute_size'], $variation->getAttributeFieldNames());
    $this->assertEquals([
      'attribute_color' => $color_attribute_value->id(),
      'attribute_size' => $size_attribute_value->id(),
    ], $variation->getAttributeValueIds());
    $this->assertEquals($color_attribute_value->id(), $variation->getAttributeValueId('attribute_color'));
    $this->assertEquals($size_attribute_value->id(), $variation->getAttributeValueId('attribute_size'));

    $this->assertEquals([
      'attribute_color' => $color_attribute_value,
      'attribute_size' => $size_attribute_value,
    ], $variation->getAttributeValues());
    $this->assertEquals($color_attribute_value, $variation->getAttributeValue('attribute_color'));
    $this->assertEquals($size_attribute_value, $variation->getAttributeValue('attribute_size'));

    // Confirm that empty fields are excluded properly.
    $variation->set('attribute_size', NULL);
    $variation->save();
    $variation = $this->reloadEntity($variation);

    $this->assertEquals([
      'attribute_color' => $color_attribute_value->id(),
    ], $variation->getAttributeValueIds());
    $this->assertNull($variation->getAttributeValueId('attribute_size'));

    $this->assertEquals([
      'attribute_color' => $color_attribute_value,
    ], $variation->getAttributeValues());
    $this->assertNull($variation->getAttributeValue('attribute_size'));

    // Confirm that deleted attribute values are handled properly.
    $variation->set('attribute_size', $size_attribute_value->id());
    $variation->save();
    $variation = $this->reloadEntity($variation);
    $size_attribute_value->delete();

    $this->assertEquals([
      'attribute_color' => $color_attribute_value->id(),
      'attribute_size' => $size_attribute_value->id(),
    ], $variation->getAttributeValueIds());

    $this->assertEquals([
      'attribute_color' => $color_attribute_value,
    ], $variation->getAttributeValues());
    $this->assertNull($variation->getAttributeValue('attribute_size'));
  }

  /**
   * @covers ::toUrl
   */
  public function testDeleteIncomplete() {
    // Confirm that a variation can be deleted even if it has no product.
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
    ]);
    $variation->save();
    $variation->delete();
  }

}
