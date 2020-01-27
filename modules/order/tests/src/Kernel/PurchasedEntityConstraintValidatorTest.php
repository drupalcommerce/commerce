<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests the purchased entity constraint on order items.
 *
 * @group commerce_order
 * @coversDefaultClass \Drupal\commerce_order\Plugin\Validation\Constraint\PurchasedEntityAvailableConstraintValidator
 */
final class PurchasedEntityConstraintValidatorTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_test',
  ];

  /**
   * Tests the availability constraint.
   *
   * @param string $sku
   *   The SKU. SKUs prefixed with TEST_* will fail availability checks.
   * @param string $order_state
   *   The order state.
   * @param bool $expected_check_result
   *   The variation status.
   * @param bool $expected_constraint
   *   The expected constraint count.
   *
   * @dataProvider dataProviderCheckerData
   * @covers ::validate
   */
  public function testAvailabilityConstraint($sku, $order_state, $expected_check_result, $expected_constraint) {
    $context = new Context($this->createUser(), $this->store);
    $checker = $this->container->get('commerce.availability_manager');

    $product_variation = $this->createTestProductVariation([
      'sku' => $sku,
      'price' => new Price('10.0', 'USD'),
    ]);
    $this->assertEquals($expected_check_result, $checker->check($product_variation, 1, $context));

    $order = Order::create([
      'type' => 'default',
      'state' => $order_state,
      'store_id' => $this->store,
    ]);
    $order_item = OrderItem::create([
      'type' => 'default',
      'order_id' => $order,
      'quantity' => '1',
      'unit_price' => $product_variation->getPrice(),
      'purchased_entity' => $product_variation->id(),
    ]);
    assert($order_item instanceof OrderItem);
    $constraints = $order_item->validate();
    if ($expected_constraint) {
      $this->assertCount(1, $constraints);
      $this->assertEquals('<em class="placeholder">test product</em> is not available with a quantity of <em class="placeholder">1</em>.', $constraints->offsetGet(0)->getMessage());
    }
    else {
      $this->assertCount(0, $constraints);
    }
  }

  /**
   * Tests the constraint does not affect non-purchasable entity order items.
   *
   * @covers ::validate
   */
  public function testValidateOrderItemWithoutPurchasedEntity() {
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'store_id' => $this->store,
    ]);
    $order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'Test order item',
      'order_id' => $order,
      'quantity' => '1',
      'unit_price' => new Price('10.00', 'USD'),
    ]);
    $constraints = $order_item->validate();
    $this->assertCount(0, $constraints);
  }

  /**
   * Tests the constraint when the purchased entity no longer exists.
   *
   * @covers ::validate
   */
  public function testPurchasedEntityNoLongerExists() {
    $product_variation = $this->createTestProductVariation([
      'sku' => 'SKU123',
      'price' => new Price('10.0', 'USD'),
    ]);

    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'store_id' => $this->store,
    ]);
    $order_item = OrderItem::create([
      'type' => 'default',
      'order_id' => $order,
      'quantity' => '1',
      'unit_price' => $product_variation->getPrice(),
      'purchased_entity' => $product_variation->id(),
    ]);
    assert($order_item instanceof OrderItem);
    $constraints = $order_item->validate();
    $this->assertCount(0, $constraints);

    $product_variation->delete();

    $constraints = $order_item->validate();
    $this->assertCount(1, $constraints);
    $constraint_messages = array_map(static function (ConstraintViolationInterface $item) {
      return $item->getMessage();
    }, \iterator_to_array($constraints->getIterator()));
    $this->assertEquals([
      'The referenced entity (<em class="placeholder">commerce_product_variation</em>: <em class="placeholder">1</em>) does not exist.',
    ], $constraint_messages);
  }

  /**
   * Tests the constraint when there is a problem selecting the store.
   *
   * @covers ::validate
   */
  public function testSelectStoresViolations() {
    $product_variation = $this->createTestProductVariation([
      'sku' => 'SKU123',
      'price' => new Price('10.0', 'USD'),
    ]);

    $order_item = OrderItem::create([
      'type' => 'default',
      'quantity' => '1',
      'unit_price' => $product_variation->getPrice(),
      'purchased_entity' => $product_variation->id(),
    ]);
    assert($order_item instanceof OrderItem);
    $constraints = $order_item->validate();
    $this->assertCount(0, $constraints);

    $product_variation->getProduct()->setStoreIds([])->save();
    $this->assertEquals([], $product_variation->getStores());
    $constraints = $order_item->validate();
    $this->assertCount(1, $constraints);
    $this->assertEquals(
      'The given entity is not assigned to any store.',
      $constraints->offsetGet(0)->getMessage()
    );

    $new_store1 = $this->createStore(NULL, NULL, 'online', FALSE);
    $new_store2 = $this->createStore(NULL, NULL, 'online', FALSE);
    $product_variation->getProduct()->setStores([$new_store1, $new_store2])->save();
    $constraints = $order_item->validate();
    $this->assertCount(1, $constraints);
    $this->assertEquals(
      "The given entity can't be purchased from the current store.",
      $constraints->offsetGet(0)->getMessage()
    );

    $product_variation->getProduct()->setStoreIds([$this->store->id()])->save();
    $constraints = $order_item->validate();
    $this->assertCount(0, $constraints);
  }

  /**
   * Data provider for test.
   *
   * @return \Generator
   *   The test data.
   */
  public function dataProviderCheckerData() {
    yield ['SKU1234', 'draft', TRUE, FALSE];
    yield ['TEST_SKU1234', 'draft', FALSE, TRUE];
    yield ['SKU1234', 'complete', TRUE, FALSE];
    yield ['TEST_SKU1234', 'complete', FALSE, FALSE];
  }

  /**
   * Create a test product variation.
   *
   * @param array $variation_data
   *   Additional variation data.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariation
   *   The test product variation.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTestProductVariation(array $variation_data) {
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = Product::create([
      'title' => 'test product',
      'type' => 'default',
      'stores' => [$this->store->id()],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductVariation $product_variation */
    $product_variation = ProductVariation::create($variation_data + [
      'type' => 'default',
    ]);
    $product_variation->save();
    $product->addVariation($product_variation);
    $product->save();
    return $this->reloadEntity($product_variation);
  }

}
