<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\EntityPrint\OrderRenderer;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\profile\Entity\Profile;

/**
 * Tests the entity_print integration.
 *
 * @group commerce
 *
 * @requires module entity_print
 */
class EntityPrintOrderRendererTest extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment',
    'entity_print',
    'commerce_order_entity_print_test',
  ];

  /**
   * Tests that the entity_print handler is set for commerce_order.
   */
  public function testEntityPrintHandlerSet() {
    $definition = $this->entityTypeManager->getDefinition('commerce_order');
    $this->assertTrue($definition->hasHandlerClass('entity_print'));
    $this->assertEquals(OrderRenderer::class, $definition->getHandlerClass('entity_print'));
  }

  /**
   * Tests the generated filename.
   */
  public function testGetFilename() {
    $sut = $this->entityTypeManager->getHandler('commerce_order', 'entity_print');
    assert($sut instanceof OrderRenderer);
    $order = Order::create([
      'id' => '123',
      'type' => 'default',
    ]);
    $filename = $sut->getFilename([$order]);
    $this->assertEquals('Order document receipt', (string) $filename);

    $second_order = Order::create([
      'id' => '789',
      'type' => 'default',
    ]);
    $filename = $sut->getFilename([$order, $second_order]);
    $this->assertEquals('Order document receipts', (string) $filename);
  }

  /**
   * Tests the rendered build output.
   */
  public function testRender() {
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();
    assert($variation1 instanceof ProductVariation);

    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();

    $payment_gateway = PaymentGateway::create([
      'id' => 'cod',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Cash on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $payment_gateway->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [$order_item1],
      'payment_gateway' => $payment_gateway->id(),
    ]);
    $order->save();

    $sut = $this->entityTypeManager->getHandler('commerce_order', 'entity_print');
    assert($sut instanceof OrderRenderer);

    $build = $sut->render([$order]);
    $this->render($build);
    $this->assertText('Thank you for your order!');
    $this->assertText('Default store');
    $this->assertText('Cash on delivery');
    $this->assertText('Order Total: $12.00');
    $this->assertText('Printed with entity_print!');
  }

}
