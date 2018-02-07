<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests orders and order items in a multilingual context.
 *
 * @group commerce
 */
class OrderMultilingualTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('sr')->save();
  }

  /**
   * Tests that the order's store is translated to current language.
   */
  public function testOrderStoreTranslated() {
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_store', 'online', TRUE);
    $this->store = $this->reloadEntity($this->store);
    $this->store->addTranslation('fr', [
      'name' => 'Magasin par défaut',
    ])->save();

    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [],
    ]);

    $this->assertEquals('Default store', $order->getStore()->label());

    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->assertEquals('Magasin par défaut', $order->getStore()->label());

    // Change the default site language and ensure the store is returned
    // even if it has not been translated to that language.
    $this->config('system.site')->set('default_langcode', 'sr')->save();
    $this->assertEquals('Default store', $order->getStore()->label());
  }

  /**
   * Tests that the order item returns a translated order item.
   */
  public function testOrderItemPurchasedEntityTranslated() {
    $variation_type = ProductVariationType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderItemType' => 'default',
      'generateTitle' => FALSE,
    ]);
    $variation_type->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', 'default', TRUE);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = ProductVariation::create([
      'type' => 'test',
      'title' => 'My Super Product',
    ]);
    $variation->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);

    $order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $variation,
    ]);

    $this->assertEquals('My Super Product', $order_item->getPurchasedEntity()->label());

    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->assertEquals('Mon super produit', $order_item->getPurchasedEntity()->label());

    // Change the default site language and ensure the purchased entity is
    // returned even if it has not been translated to that language.
    $this->config('system.site')->set('default_langcode', 'sr')->save();
    $this->assertEquals('My Super Product', $order_item->getPurchasedEntity()->label());
  }

}
